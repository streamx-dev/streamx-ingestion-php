<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Builders;

use Streamx\Clients\Ingestion\Impl\RestStreamxClientBuilder;
use Streamx\Clients\Ingestion\StreamxClientBuilder;

/**
 * Provides convenient access to {@link StreamxClientBuilder} instance.
 */
class StreamxClientBuilders
{
    /**
     * Creates default {@link StreamxClientBuilder} instance.
     * @param string $serverUrl StreamX REST API server URL.
     * @return StreamxClientBuilder Created {@link StreamxClientBuilder} instance.
     */
    public static function create(string $serverUrl): StreamxClientBuilder
    {
        return new RestStreamxClientBuilder($serverUrl);
    }
}