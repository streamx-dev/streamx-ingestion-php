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
use Streamx\Clients\Ingestion\Impl\Utils\MultipleJsonsSplitter;
use Streamx\Clients\Ingestion\Publisher\HttpRequester;

class GuzzleHttpRequester implements HttpRequester
{

    private ClientInterface $httpClient;

    public function __construct(?ClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient ?? new GuzzleHttpClient();
    }

    public function isIngestionServiceAvailable(UriInterface $endpointUri): bool {
        try {
            $request = new Request('GET', $endpointUri);
            $response = $this->httpClient->sendRequest($request);
            return $this->parseHealthCheckResponse($response);
        } catch (ClientExceptionInterface $e) {
            throw new StreamxClientException(
                sprintf('HealthCheck GET request with URI: %s failed due to HTTP client error', $endpointUri),
                $e);
        }
    }

    public function performIngestion(UriInterface $endpointUri, array $headers, string $json): array {
        try {
            $request = new Request('POST', $endpointUri, $headers, $json);
            $response = $this->httpClient->sendRequest($request);
            return $this->handleIngestionResponse($response);
        } catch (ClientExceptionInterface $e) {
            throw new StreamxClientException(
                sprintf('Ingestion POST request with URI: %s failed due to HTTP client error', $endpointUri),
                $e);
        }
    }

    public function fetchSchema(UriInterface $endpointUri, array $headers): string {
        try {
            $request = new Request('GET', $endpointUri, $headers);
            $response = $this->httpClient->sendRequest($request);
            return $this->handleSchemaResponse($response);
        } catch (ClientExceptionInterface $e) {
            throw new StreamxClientException(
                sprintf('Schema GET request with URI: %s failed due to HTTP client error', $endpointUri),
                $e);
        }
    }

    private function parseHealthCheckResponse(ResponseInterface $response): bool
    {
        if ($response->getStatusCode() == 200) {
            $responseAsArray = json_decode((string)$response->getBody(), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return isset($responseAsArray['status']) && $responseAsArray['status'] === 'UP';
            }
        }
        return false;
    }

    /**
     * @return MessageStatus[]
     * @throws StreamxClientException
     */
    private function handleIngestionResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();

        switch ($statusCode) {
            case 202:
                return $this->parseMessageStatuses($response);
            case 401:
                throw $this->createAuthenticationException();
            case in_array($statusCode, [400, 403, 500]):
                $failureResponse = $this->parseFailureResponse($response);
                throw $this->streamxClientExceptionFrom($failureResponse);
            default:
                throw $this->createGenericCommunicationException($response);
        }
    }

    /**
     * @throws StreamxClientException
     */
    private function handleSchemaResponse(ResponseInterface $response): string
    {
        $statusCode = $response->getStatusCode();
        switch ($statusCode) {
            case 200:
                return (string)$response->getBody();
            case 401:
                throw $this->createAuthenticationException();
            default:
                throw $this->createGenericCommunicationException($response);
        }
    }

    /**
     * @return MessageStatus[] array
     * @throws StreamxClientException
     */
    private function parseMessageStatuses(ResponseInterface $response): array
    {
        $jsonObjects = $this->parseResponseToJsonObjects($response);
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
            $jsonString = (string)$response->getBody();
            $jsonObject = $this->parseToJsonObject($jsonString, $response);
            return FailureResponse::fromJson($jsonObject);
        } catch (DataValidationException $e) {
            throw new StreamxClientException(sprintf('Communication error. Response status: %s. Message: %s',
                $response->getStatusCode(), $e->getMessage()));
        }
    }

    /**
     * @throws StreamxClientException
     */
    private function parseResponseToJsonObjects(ResponseInterface $response): array
    {
        $responseBody = (string) $response->getBody();
        $singleResponseJsons = MultipleJsonsSplitter::splitToSingleJsons($responseBody);

        $jsonObjects = [];
        foreach ($singleResponseJsons as $singleResponseJson) {
            $jsonObjects[] = $this->parseToJsonObject($singleResponseJson, $response);
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

    /**
     * @throws StreamxClientException
     */
    private function parseToJsonObject(string $json, ResponseInterface $response)
    {
        $jsonObject = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new StreamxClientException(sprintf('Communication error. Response status: %s. Message: %s',
                $response->getStatusCode(), 'Response could not be parsed.'));
        }
        return $jsonObject;
    }

    private static function createAuthenticationException(): StreamxClientException
    {
        return new StreamxClientException('Authentication failed. Make sure that the given token is valid.');
    }

    private static function createGenericCommunicationException(ResponseInterface $response): StreamxClientException
    {
        return new StreamxClientException(
            sprintf('Communication error. Response status: %s. Message: %s',
                $response->getStatusCode(),
                $response->getReasonPhrase()));
    }
}