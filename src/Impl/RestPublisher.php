<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientExceptionFactory;
use Streamx\Clients\Ingestion\Impl\Utils\HttpUtils;
use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;
use Streamx\Clients\Ingestion\Publisher\Message;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use Streamx\Clients\Ingestion\Publisher\SuccessResult;

class RestPublisher extends Publisher
{
    private const HEALTH_CHECK_ENDPOINT_PATH_TEMPLATE = '%s/q/health';
    private const INGESTION_ENDPOINT_PATH_TEMPLATE = '%s/channels/%s/messages';
    private const SCHEMA_ENDPOINT_PATH_TEMPLATE = '%s/channels/%s/schema';

    private array $headers;
    private UriInterface $healthCheckEndpointUri;
    private UriInterface $ingestionEndpointUri;
    private UriInterface $schemaEndpointUri;
    private string $payloadTypeName;
    private HttpRequester $httpRequester;
    private JsonProvider $jsonProvider;

    public function __construct(
        string $serverUrl,
        string $ingestionEndpointBasePath,
        string $channel,
        string $channelSchemaName,
        ?string $authToken,
        HttpRequester $httpRequester,
        JsonProvider $jsonProvider
    ) {
        $ingestionEndpointBaseUri = HttpUtils::buildAbsoluteUri($serverUrl . $ingestionEndpointBasePath);
        $this->headers = $this->buildHttpHeaders($authToken);
        $this->healthCheckEndpointUri = HttpUtils::buildUri(sprintf(self::HEALTH_CHECK_ENDPOINT_PATH_TEMPLATE, $serverUrl));
        $this->ingestionEndpointUri = HttpUtils::buildUri(sprintf(self::INGESTION_ENDPOINT_PATH_TEMPLATE, $ingestionEndpointBaseUri, $channel));
        $this->schemaEndpointUri = HttpUtils::buildUri(sprintf(self::SCHEMA_ENDPOINT_PATH_TEMPLATE, $ingestionEndpointBaseUri, $channel));
        $this->payloadTypeName = self::convertToPayloadTypeName($channelSchemaName);
        $this->httpRequester = $httpRequester;
        $this->jsonProvider = $jsonProvider;
    }

    private static function convertToPayloadTypeName($channelSchemaName): string
    {
        $payloadTypeName = preg_replace('/IngestionMessage$/', '', $channelSchemaName);
        if ($payloadTypeName == $channelSchemaName)
        {
            throw new InvalidArgumentException("Expected the provided channel schema name '$channelSchemaName' to end with 'IngestionMessage'");
        }
        return $payloadTypeName;
    }

    public function isIngestionServiceAvailable(): bool
    {
        return $this->httpRequester->isIngestionServiceAvailable($this->healthCheckEndpointUri);
    }

    public function send(Message $message): SuccessResult
    {
        $messageStatus = $this->sendMulti([$message])[0];

        if ($messageStatus->getSuccess() != null) {
            return $messageStatus->getSuccess();
        }

        $failureResponse = $messageStatus->getFailure();
        throw StreamxClientExceptionFactory::create(
            $failureResponse->getErrorCode(),
            $failureResponse->getErrorMessage()
        );
    }

    public function sendMulti(array $messages): array
    {
        $multiMessageJson = '';
        foreach ($messages as $message) {
            $multiMessageJson .= $this->jsonProvider->getJson($message, $this->payloadTypeName);
        }

        $actualHeaders = array_merge($this->headers, ['Content-Type' => 'application/json; charset=UTF-8']);
        return $this->httpRequester->performIngestion($this->ingestionEndpointUri, $actualHeaders, $multiMessageJson);
    }

    public function fetchSchema(): string
    {
        return $this->httpRequester->fetchSchema($this->schemaEndpointUri, $this->headers);
    }

    private function buildHttpHeaders(?string $authToken): array
    {
        if (empty($authToken)) {
            return [];
        }
        return ['Authorization' => 'Bearer ' . $authToken];
    }

}