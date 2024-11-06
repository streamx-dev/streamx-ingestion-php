<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Impl\Utils\DataValidationException;
use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\SuccessResult;

class GuzzleHttpRequester implements HttpRequester
{

    public function __construct(
        private readonly ClientInterface $httpClient = new GuzzleHttpClient()
    ) {
    }

    public function executePost(
        UriInterface $endpointUri,
        array $headers,
        string $json
    ): SuccessResult {
        try {
            $request = new Request('POST', $endpointUri, $headers, $json);
            $response = $this->httpClient->sendRequest($request);
            return $this->handleResponse($response);
        } catch (ClientExceptionInterface $e) {
            throw new StreamxClientException(
                sprintf('POST request with URI: %s failed due to HTTP client error', $endpointUri),
                $e);
        }
    }

    /**
     * @throws StreamxClientException
     */
    private function handleResponse(ResponseInterface $response): SuccessResult
    {
        $statusCode = $response->getStatusCode();
        switch ($statusCode) {
            // TODO: introduce full exceptions hierarchy (split StreamxClientException), as in StreamX Java Client
            case 202:
                $messageStatus = $this->parseMessageStatus($response);
                if ($messageStatus->getSuccess() != null) {
                    return $messageStatus->getSuccess();
                } else {
                    throw $this->streamxClientExceptionFrom($messageStatus->getFailure());
                }
            case 400:
            case 403:
            case 500:
                $failureResponse = $this->parseFailureResponse($response);
                throw $this->streamxClientExceptionFrom($failureResponse);
            case 401:
                throw new StreamxClientException('Authentication failed. Make sure that the given token is valid.');
            default:
                throw new StreamxClientException(
                    sprintf('Communication error. Response status: %s. Message: %s',
                        $statusCode,
                        $response->getReasonPhrase()));
        }
    }

    /**
     * @throws StreamxClientException
     */
    private function parseMessageStatus(ResponseInterface $response): MessageStatus
    {
        return $this->parseResponse(
            $response,
            fn($json) => MessageStatus::fromJson($json)
        );
    }

    /**
     * @throws StreamxClientException
     */
    private function parseFailureResponse(ResponseInterface $response): FailureResponse
    {
        return $this->parseResponse(
            $response,
            fn($json) => FailureResponse::fromJson($json)
        );
    }

    /**
     * @throws StreamxClientException
     */
    private function parseResponse(ResponseInterface $response, callable $jsonToObjectMapper): mixed
    {
        try {
            $jsonObject = $this->parseResponseToJson($response);
            return $jsonToObjectMapper($jsonObject);
        } catch (DataValidationException $e) {
            throw new StreamxClientException(sprintf('Communication error. Response status: %s. Message: %s',
                $response->getStatusCode(), $e->getMessage()));
        }
    }

    /**
     * @throws StreamxClientException
     */
    private function parseResponseToJson(ResponseInterface $response): mixed
    {
        $jsonString = (string)$response->getBody();
        $jsonObject = json_decode($jsonString);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new StreamxClientException(sprintf('Communication error. Response status: %s. Message: %s',
                $response->getStatusCode(), 'Response could not be parsed.'));
        }
        return $jsonObject;
    }

    private function streamxClientExceptionFrom(FailureResponse $failureResponse): StreamxClientException{
        $errorCode = $failureResponse->getErrorCode();
        $exceptionMessage = sprintf(
            'Ingestion REST endpoint known error. Code: %s. Message: %s',
            $errorCode, $failureResponse->getErrorMessage()
        );
        return new StreamxClientException($exceptionMessage);
      }
}