<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;
use Streamx\Clients\Ingestion\Publisher\Publisher;

class RestPublisherProvider
{
    private string $serverUrl;
    private string $ingestionEndpointBasePath;
    private ?string $authToken;
    private HttpRequester $httpRequester;
    private JsonProvider $jsonProvider;

    public function __construct(
        string $serverUrl,
        string $ingestionEndpointBasePath,
        ?string $authToken,
        HttpRequester $httpRequester,
        JsonProvider $jsonProvider)
    {
        $this->serverUrl = $serverUrl;
        $this->ingestionEndpointBasePath = $ingestionEndpointBasePath;
        $this->authToken = $authToken;
        $this->httpRequester = $httpRequester;
        $this->jsonProvider = $jsonProvider;
    }

    public function newPublisher(string $channel, string $channelSchemaName): Publisher
    {
        return new RestPublisher(
            $this->serverUrl,
            $this->ingestionEndpointBasePath,
            $channel,
            $channelSchemaName,
            $this->authToken,
            $this->httpRequester,
            $this->jsonProvider
        );
    }
}