<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use Psr\Http\Message\UriInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Impl\Utils\HttpUtils;
use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use Streamx\Clients\Ingestion\Publisher\PublisherSuccessResult;

class RestPublisher implements Publisher
{
    private array $headers;

    public function __construct(
        private readonly UriInterface $publicationsEndpointUri,
        private readonly string $channel,
        ?string $authToken,
        private readonly HttpRequester $httpRequester,
        private readonly JsonProvider $jsonProvider
    ) {
        $this->headers = $this->buildHttpHeaders($authToken);
    }

    public function publish(string $key, object|array $data): PublisherSuccessResult
    {
        $json = $this->jsonProvider->getJson($data);
        $endpointUri = $this->buildPublicationsUri($key);
        return $this->httpRequester->executePut($endpointUri, $this->headers, $json);
    }

    public function unpublish(string $key): PublisherSuccessResult
    {
        $endpointUri = $this->buildPublicationsUri($key);
        return $this->httpRequester->executeDelete($endpointUri, $this->headers);
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
    private function buildPublicationsUri(string $key): UriInterface
    {
        return HttpUtils::buildUri("$this->publicationsEndpointUri/$this->channel/$key");
    }
}