<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Testing\Impl;

use Psr\Http\Message\UriInterface;
use Streamx\Clients\Ingestion\Impl\GuzzleHttpRequester;
use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\PublisherSuccessResult;
use Streamx\Clients\Ingestion\StreamxClient;

class CustomTestHttpRequester implements HttpRequester
{

    public function __construct(
        private readonly string $publicationsEndpointPath,
        private readonly HttpRequester $httpRequester = new GuzzleHttpRequester()
    ) {
    }

    public function executePut(
        UriInterface $endpointUri,
        array $headers,
        string $json
    ): PublisherSuccessResult {
        $endpointUri = $this->modifyUri($endpointUri);
        return $this->httpRequester->executePut($endpointUri, $headers, $json);
    }

    public function executeDelete(UriInterface $endpointUri, array $headers): PublisherSuccessResult
    {
        $endpointUri = $this->modifyUri($endpointUri);
        return $this->httpRequester->executeDelete($endpointUri, $headers);
    }

    private function modifyUri(UriInterface $uri): UriInterface
    {
        return $uri->withPath(
            str_replace(StreamxClient::PUBLICATIONS_ENDPOINT_PATH_V1,
                $this->publicationsEndpointPath, $uri->getPath())
        );
    }
}