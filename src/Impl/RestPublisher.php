<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use Psr\Http\Message\UriInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Impl\Message;
use Streamx\Clients\Ingestion\Impl\Utils\HttpUtils;
use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use Streamx\Clients\Ingestion\Publisher\SuccessResult;

class RestPublisher implements Publisher
{
    private array $headers;
    private UriInterface $messageIngestionEndpointUri;

    public function __construct(
        UriInterface $ingestionEndpointUri,
        private readonly string $channel,
        private readonly string $channelSchemaJson,
        ?string $authToken,
        private readonly HttpRequester $httpRequester,
        private readonly JsonProvider $jsonProvider
    ) {
        $this->headers = $this->buildHttpHeaders($authToken);
        $this->messageIngestionEndpointUri = $this->buildMessageIngestionUri($ingestionEndpointUri, $channel);
    }

    public function publish(string $key, object|array $payload): SuccessResult
    {
        $message = Message::newPublishMessage($key, $payload);
        $actualHeaders = array_merge($this->headers, ['Content-Type' => 'application/json; charset=UTF-8']);
        return $this->ingest($message, $actualHeaders);
    }

    public function unpublish(string $key): SuccessResult
    {
        $message = Message::newUnpublishMessage($key);
        return $this->ingest($message, $this->headers);
    }

    private function ingest(Message $message, $headers): SuccessResult
    {
        $json = $this->jsonProvider->getJson($message, $this->channelSchemaJson);
        return $this->httpRequester->executePost($this->messageIngestionEndpointUri, $headers, $json);
    }

    private function buildHttpHeaders(?string $authToken): array
    {
        if (empty($authToken)) {
            return [];
        }
        return ['Authorization' => 'Bearer ' . $authToken];
    }

    /**
     * @throws StreamxClientException
     */
    private function buildMessageIngestionUri(UriInterface $ingestionEndpointUri, string $channel): UriInterface
    {
        return HttpUtils::buildUri("$ingestionEndpointUri/channels/$channel/messages");
    }
}