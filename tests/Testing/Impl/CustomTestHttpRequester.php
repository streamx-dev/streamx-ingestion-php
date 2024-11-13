<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Testing\Impl;

use Psr\Http\Message\UriInterface;
use Streamx\Clients\Ingestion\Impl\GuzzleHttpRequester;
use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\SuccessResult;
use Streamx\Clients\Ingestion\StreamxClient;

class CustomTestHttpRequester implements HttpRequester
{

    private string $ingestionEndpointPath;
    private HttpRequester $httpRequester;

    public function __construct(string $ingestionEndpointPath)
    {
        $this->ingestionEndpointPath = $ingestionEndpointPath;
        $this->httpRequester = new GuzzleHttpRequester();
    }

    public function executePost(
        UriInterface $endpointUri,
        array $headers,
        string $json
    ): SuccessResult {
        $endpointUri = $this->modifyUri($endpointUri);
        return $this->httpRequester->executePost($endpointUri, $headers, $json);
    }

    private function modifyUri(UriInterface $uri): UriInterface
    {
        return $uri->withPath(
            str_replace(StreamxClient::INGESTION_ENDPOINT_PATH_V1,
                $this->ingestionEndpointPath, $uri->getPath())
        );
    }
}