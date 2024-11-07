<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Impl\Utils\HttpUtils;
use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use Streamx\Clients\Ingestion\StreamxClient;

class RestStreamxClient implements StreamxClient
{
    private RestPublisherProvider $publisherProvider;

    /**
     * @throws StreamxClientException
     */
    public function __construct(
        string $serverUrl,
        string $ingestionEndpointPath,
        ?string $authToken,
        HttpRequester $httpRequester,
        JsonProvider $jsonProvider
    ) {
        $ingestionEndpointUri = HttpUtils::buildAbsoluteUri($serverUrl . $ingestionEndpointPath);
        $this->publisherProvider = new RestPublisherProvider($ingestionEndpointUri, $authToken,
            $httpRequester, $jsonProvider);
    }

    public function newPublisher(string $channel, string $channelSchemaJson): Publisher
    {
        return $this->publisherProvider->newPublisher($channel, $channelSchemaJson);
    }
}