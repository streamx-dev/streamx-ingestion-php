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

    public function executePost(
        UriInterface $endpointUri,
        array $headers,
        string $json
    ): array {
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
     * @return MessageStatus[]
     * @throws StreamxClientException
     */
    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode == 202) {
            return $this->parseMessageStatuses($response);
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
}