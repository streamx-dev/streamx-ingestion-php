<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use Psr\Http\Message\UriInterface;
use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;
use Streamx\Clients\Ingestion\Publisher\Publisher;

class RestPublisherProvider
{

    private UriInterface $ingestionEndpointUri;
    private ?string $authToken;
    private HttpRequester $httpRequester;
    private JsonProvider $jsonProvider;

    public function __construct(
        UriInterface $ingestionEndpointUri,
        ?string $authToken,
        HttpRequester $httpRequester,
        JsonProvider $jsonProvider)
    {
        $this->ingestionEndpointUri = $ingestionEndpointUri;
        $this->authToken = $authToken;
        $this->httpRequester = $httpRequester;
        $this->jsonProvider = $jsonProvider;
    }

    public function newPublisher(string $channel, string $channelSchemaName): Publisher
    {
        return new RestPublisher(
            $this->ingestionEndpointUri,
            $channel,
            $channelSchemaName,
            $this->authToken,
            $this->httpRequester,
            $this->jsonProvider
        );
    }
}