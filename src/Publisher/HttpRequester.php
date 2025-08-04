<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Publisher;

use Psr\Http\Message\UriInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;

/**
 * An interface that allows to inject custom HTTP client implementation.
 */
interface HttpRequester
{
    /**
     * Executes a StreamX Ingestion POST request.
     * @param UriInterface $endpointUri Request target URI.
     * @param array $headers Request headers.
     * @param string $json Request JSON.
     * @param array $additionalRequestOptions Additional request options. Optional.
     *   With default implementation, the supported options are: https://docs.guzzlephp.org/en/stable/request-options.html
     * @return MessageStatus[] with SuccessResult and/or FailureResponse of processing messages in the request
     * @throws StreamxClientException if request failed.
     */
    public function performIngestion(
        UriInterface $endpointUri,
        array $headers,
        string $json,
        array $additionalRequestOptions = []
    ): array;
}