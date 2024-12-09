<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientExceptionFactory;
use Streamx\Clients\Ingestion\Impl\Utils\DataValidationException;
use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\SuccessResult;

class GuzzleHttpRequester implements HttpRequester
{

    private ClientInterface $httpClient;

    public function __construct(?ClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient ?? new GuzzleHttpClient();
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

        if ($statusCode == 202) {
            $messageStatuses = $this->parseMessageStatuses($response);
            foreach ($messageStatuses as $messageStatus) {
                if ($messageStatus->getFailure() != null) {
                    throw $this->streamxClientExceptionFrom($messageStatus->getFailure()); // TODO: handle collecting all failures into this response exception
                }
            }
            return $messageStatuses[0]->getSuccess(); // TODO: handle returning list of statuses, not just the first success
        }

        if ($statusCode == 401) {
            throw new StreamxClientException('Authentication failed. Make sure that the given token is valid.');
        }

        if (in_array($statusCode, [400, 403, 500])) {
            $failureResponse = $this->parseFailureResponse($response);
            throw $this->streamxClientExceptionFrom($failureResponse);
        }

        throw new StreamxClientException(
            sprintf('Communication error. Response status: %s. Message: %s',
                $statusCode,
                $response->getReasonPhrase()));
    }

    /**
     * @return MessageStatus[] array
     * @throws StreamxClientException
     */
    private function parseMessageStatuses(ResponseInterface $response): array
    {
        $jsonObjects = $this->parseResponseToJsons($response);
        $messageStatuses = [];

        foreach ($jsonObjects as $jsonObject) {
            $messageStatuses[] = MessageStatus::fromJson($jsonObject);
        }

        return $messageStatuses;
    }

    /**
     * @throws StreamxClientException
     */
    private function parseFailureResponse(ResponseInterface $response): FailureResponse
    {
        try {
            $jsonObjects = $this->parseResponseToJsons($response);
            $jsonObjectsCount = count($jsonObjects);
            if ($jsonObjectsCount !== 1) {
                throw new StreamxClientException('Expected a single failure response. Got: ' . $jsonObjectsCount);
            }
            return FailureResponse::fromJson($jsonObjects[0]);
        } catch (DataValidationException $e) {
            throw new StreamxClientException(sprintf('Communication error. Response status: %s. Message: %s',
                $response->getStatusCode(), $e->getMessage()));
        }
    }

    /**
     * @throws StreamxClientException
     */
    private function parseResponseToJsons(ResponseInterface $response)
    {
        $compositeResponseJson = (string)$response->getBody();
        $singleResponseJsons = explode("\n", $compositeResponseJson); // TODO assuming the only newline characters possible in response, is the one that separates multiple MessageStatus jsons

        $jsonObjects = [];
        foreach ($singleResponseJsons as $singleResponseJson) {
            if (empty($singleResponseJson)) {
                continue;
            }
            $jsonObjects[] = json_decode($singleResponseJson);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new StreamxClientException(sprintf('Communication error. Response status: %s. Message: %s',
                    $response->getStatusCode(), 'Response could not be parsed.'));
            }
        }
        return $jsonObjects;
    }

    private function streamxClientExceptionFrom(FailureResponse $failureResponse): StreamxClientException
    {
        return StreamxClientExceptionFactory::create(
            $failureResponse->getErrorCode(),
            $failureResponse->getErrorMessage()
        );
    }
}