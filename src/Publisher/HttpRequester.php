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
     * Performs PUT request.
     * @param UriInterface $endpointUri Request target URI.
     * @param array $headers Request headers.
     * @param string $json Request JSON.
     * @return PublisherSuccessResult
     * @throws StreamxClientException if request failed.
     */
    public function executePut(
        UriInterface $endpointUri,
        array $headers,
        string $json
    ): PublisherSuccessResult;

    /**
     * Performs DELETE request.
     * @param UriInterface $endpointUri Request target URI.
     * @param array $headers Request headers.
     * @return PublisherSuccessResult
     * @throws StreamxClientException if request failed.
     */
    public function executeDelete(
        UriInterface $endpointUri,
        array $headers
    ): PublisherSuccessResult;
}