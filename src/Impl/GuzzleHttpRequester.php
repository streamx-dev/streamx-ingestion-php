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
use Streamx\Clients\Ingestion\Publisher\PublisherSuccessResult;

class GuzzleHttpRequester implements HttpRequester
{

    public function __construct(
        private readonly ClientInterface $httpClient = new GuzzleHttpClient()
    ) {
    }

    public function executePut(
        UriInterface $endpointUri,
        array $headers,
        string $json
    ): PublisherSuccessResult {
        try {
            $actualHeaders = ['Content-Type' => 'application/json; charset=UTF-8'];
            $actualHeaders = array_merge($actualHeaders, $headers);
            $request = new Request('PUT', $endpointUri, $actualHeaders, $json);
            $response = $this->httpClient->sendRequest($request);
            return $this->handleResponse($response);
        } catch (ClientExceptionInterface $e) {
            throw new StreamxClientException(
                sprintf('PUT request with URI: %s failed due to HTTP client error', $endpointUri),
                $e);
        }
    }

    public function executeDelete(UriInterface $endpointUri, array $headers): PublisherSuccessResult
    {
        try {
            $request = new Request('DELETE', $endpointUri, $headers);
            $response = $this->httpClient->sendRequest($request);
            return $this->handleResponse($response);
        } catch (ClientExceptionInterface $e) {
            throw new StreamxClientException(
                sprintf('DELETE request with URI: %s failed due to HTTP client error',
                    $endpointUri),
                $e);
        }
    }

    /**
     * @throws StreamxClientException
     */
    private function handleResponse(ResponseInterface $response): PublisherSuccessResult
    {
        $statusCode = $response->getStatusCode();
        switch ($statusCode) {
            case 202:
                return $this->parseSuccessResponse($response);
            case 400:
            case 500:
                $failureResponse = $this->parseFailureResponse($response);
                throw new StreamxClientException(
                    sprintf('Publication Ingestion REST endpoint known error. Code: %s. Message: %s',
                        $failureResponse->getErrorCode(),
                        $failureResponse->getErrorMessage()));
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
    private function parseSuccessResponse(ResponseInterface $response): PublisherSuccessResult
    {
        return $this->parseResponse($response,
            fn($json) => PublisherSuccessResultDeserializer::fromJson($json));
    }

    /**
     * @throws StreamxClientException
     */
    private function parseFailureResponse(ResponseInterface $response): FailureResponse
    {
        return $this->parseResponse($response, fn($json) => FailureResponse::fromJson($json));
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
}