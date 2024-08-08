<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use Psr\Http\Client\ClientInterface;
use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;
use Streamx\Clients\Ingestion\StreamxClient;
use Streamx\Clients\Ingestion\StreamxClientBuilder;

class RestStreamxClientBuilder implements StreamxClientBuilder
{
    private ?string $publicationsEndpointUri = null;
    private ?string $authToken = null;
    private ?HttpRequester $httpRequester = null;
    private ?ClientInterface $httpClient = null;
    private ?JsonProvider $jsonProvider = null;

    public function __construct(private readonly string $serverUrl)
    {
    }

    public function setPublicationsEndpointUri(string $publicationsEndpointUri
    ): StreamxClientBuilder {
        $this->publicationsEndpointUri = $publicationsEndpointUri;
        return $this;
    }

    public function setAuthToken(string $authToken): StreamxClientBuilder
    {
        $this->authToken = $authToken;
        return $this;
    }

    public function setHttpRequester(HttpRequester $httpRequester): StreamxClientBuilder
    {
        $this->httpRequester = $httpRequester;
        return $this;
    }

    public function setHttpClient(ClientInterface $httpClient): StreamxClientBuilder
    {
        $this->httpClient = $httpClient;
        return $this;
    }

    public function setJsonProvider(JsonProvider $jsonProvider): StreamxClientBuilder
    {
        $this->jsonProvider = $jsonProvider;
        return $this;
    }

    public function build(): StreamxClient
    {
        $this->ensurePublicationsEndpointUri();
        $this->ensureHttpRequester();
        $this->ensureJsonProvider();
        return new RestStreamxClient($this->serverUrl, $this->publicationsEndpointUri,
            $this->authToken, $this->httpRequester, $this->jsonProvider);
    }

    private function ensurePublicationsEndpointUri(): void
    {
        if ($this->publicationsEndpointUri == null) {
            $this->publicationsEndpointUri = StreamxClient::PUBLICATIONS_ENDPOINT_PATH_V1;
        }
    }

    private function ensureHttpRequester(): void
    {
        if ($this->httpRequester == null) {
            if ($this->httpClient == null) {
                $this->httpRequester = new GuzzleHttpRequester();
            } else {
                $this->httpRequester = new GuzzleHttpRequester($this->httpClient);
            }
        }
    }

    private function ensureJsonProvider(): void
    {
        if ($this->jsonProvider == null) {
            $this->jsonProvider = new DefaultJsonProvider();
        }
    }
}