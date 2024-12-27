<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion;

use Psr\Http\Client\ClientInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;

/**
 * Builder for customized {@link StreamxClient} instances.
 */
interface StreamxClientBuilder
{

    /**
     * Configures custom StreamX REST Ingestion base path. Default value is
     * {@link StreamxClient::INGESTION_ENDPOINT_BASE_PATH}.
     * @param string $ingestionEndpointBasePath StreamX REST Ingestion base path.
     * @return StreamxClientBuilder
     */
    public function setIngestionEndpointBasePath(string $ingestionEndpointBasePath): StreamxClientBuilder;

    /**
     * Configures authentication token.
     * @param string $authToken Auth token used for authentication on the server side.
     * @return StreamxClientBuilder
     */
    public function setAuthToken(string $authToken): StreamxClientBuilder;

    /**
     * Configures custom {@link HttpRequester}. If set then custom {@link Client} set by
     * {@link StreamxClientBuilder::setHttpClient} is ignored.
     * @param HttpRequester $httpRequester Custom {@link HttpRequester}.
     * @return StreamxClientBuilder
     */
    public function setHttpRequester(HttpRequester $httpRequester): StreamxClientBuilder;

    /**
     * Configures custom {@link ClientInterface} for default {@link HttpRequester}. It is ignored when
     * custom {@link HttpRequester} is set using {@link StreamxClientBuilder::setHttpRequester()}.
     * @param ClientInterface $httpClient Custom {@link Client} instance.
     * @return StreamxClientBuilder
     */
    public function setHttpClient(ClientInterface $httpClient): StreamxClientBuilder;

    /**
     * Configures custom {@link JsonProvider}.
     * @param JsonProvider $jsonProvider Custom {@link JsonProvider}
     * @return StreamxClientBuilder
     */
    public function setJsonProvider(JsonProvider $jsonProvider): StreamxClientBuilder;

    /**
     * Builds actual {@link StreamxClient} instance.
     * @return StreamxClient
     * @throws StreamxClientException if {@link StreamxClient} instance could not be created.
     */
    public function build(): StreamxClient;
}