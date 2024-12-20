<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use Psr\Http\Client\ClientInterface;
use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;
use Streamx\Clients\Ingestion\StreamxClient;
use Streamx\Clients\Ingestion\StreamxClientBuilder;

class RestStreamxClientBuilder implements StreamxClientBuilder
{
    private string $serverUrl;
    private ?string $ingestionEndpointBaseUri = null;
    private ?string $authToken = null;
    private ?HttpRequester $httpRequester = null;
    private ?ClientInterface $httpClient = null;
    private ?JsonProvider $jsonProvider = null;

    public function __construct(string $serverUrl)
    {
        $this->serverUrl = $serverUrl;
    }

    public function setIngestionEndpointBaseUri(string $ingestionEndpointBaseUri): StreamxClientBuilder
    {
        $this->ingestionEndpointBaseUri = $ingestionEndpointBaseUri;
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
        $this->ensureIngestionEndpointUri();
        $this->ensureHttpRequester();
        $this->ensureJsonProvider();
        return new RestStreamxClient($this->serverUrl, $this->ingestionEndpointBaseUri,
            $this->authToken, $this->httpRequester, $this->jsonProvider);
    }

    private function ensureIngestionEndpointUri(): void
    {
        if ($this->ingestionEndpointBaseUri == null) {
            $this->ingestionEndpointBaseUri = StreamxClient::INGESTION_ENDPOINT_BASE_PATH;
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