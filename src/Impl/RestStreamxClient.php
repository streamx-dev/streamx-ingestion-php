<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use Streamx\Clients\Ingestion\StreamxClient;

class RestStreamxClient implements StreamxClient
{
    private RestPublisherProvider $publisherProvider;

    public function __construct(
        string $serverUrl,
        string $ingestionEndpointBasePath,
        ?string $authToken,
        HttpRequester $httpRequester,
        JsonProvider $jsonProvider
    ) {
        $this->publisherProvider = new RestPublisherProvider($serverUrl, $ingestionEndpointBasePath, $authToken,
            $httpRequester, $jsonProvider);
    }

    public function newPublisher(string $channel, string $channelSchemaName): Publisher
    {
        return $this->publisherProvider->newPublisher($channel, $channelSchemaName);
    }
}