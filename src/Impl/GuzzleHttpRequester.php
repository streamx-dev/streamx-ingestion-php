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

    /**
     * @return MessageStatus[]
     * @throws StreamxClientException
     */
    private function handleIngestionResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();

        switch ($statusCode) {
            case 202:
                return $this->parseStreamedMessageStatuses($response);
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
     * @return MessageStatus[] array
     * @throws StreamxClientException
     */
    private function parseStreamedMessageStatuses(ResponseInterface $response): array
    {
        $jsonObjects = $this->parseStreamedResponseToJsonObjects($response);
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
    private function parseStreamedResponseToJsonObjects(ResponseInterface $response): array
    {
        $responseBody = $this->readStreamedResponseBody($response);
        $singleResponseJsons = MultipleJsonsSplitter::splitToSingleJsons($responseBody);

        $jsonObjects = [];
        foreach ($singleResponseJsons as $singleResponseJson) {
            $jsonObjects[] = $this->parseToJsonObject($singleResponseJson, $response);
        }
        return $jsonObjects;
    }

    private function readStreamedResponseBody(ResponseInterface $response): string
    {
        $responseBody = '';
        $bodyReader = $response->getBody();
        while (!$bodyReader->eof()) {
            $responseBody .= $bodyReader->read(1024);
        }
        return $responseBody;
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