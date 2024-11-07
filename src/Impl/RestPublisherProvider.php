<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use Psr\Http\Message\UriInterface;
use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;
use Streamx\Clients\Ingestion\Publisher\Publisher;

class RestPublisherProvider
{

    public function __construct(
        private readonly UriInterface $ingestionEndpointUri,
        private readonly ?string $authToken,
        private readonly HttpRequester $httpRequester,
        private readonly JsonProvider $jsonProvider
    ) {
    }

    public function newPublisher(string $channel, string $channelSchemaJson): Publisher
    {
        return new RestPublisher(
            $this->ingestionEndpointUri,
            $channel,
            $channelSchemaJson,
            $this->authToken,
            $this->httpRequester,
            $this->jsonProvider
        );
    }
}