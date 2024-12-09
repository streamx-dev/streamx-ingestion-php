<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Publisher;

use Psr\Http\Message\UriInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Impl\MessageStatus;

/**
 * An interface that allows to inject custom HTTP client implementation.
 */
interface HttpRequester
{
    /**
     * Performs POST request.
     * @param UriInterface $endpointUri Request target URI.
     * @param array $headers Request headers.
     * @param string $json Request JSON.
     * @return MessageStatus[] with SuccessResult and/or FailureResponse of processing messages in the request
     * @throws StreamxClientException if request failed.
     */
    public function executePost(
        UriInterface $endpointUri,
        array $headers,
        string $json
    ): array;
}