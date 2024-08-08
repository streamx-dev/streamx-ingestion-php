<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion;

use Streamx\Clients\Ingestion\Publisher\Publisher;

/**
 * Represents a client that can make publications to StreamX Ingestion Service.
 */
interface StreamxClient
{
    /**
     * StreamX REST Ingestion publications path.
     */
    public const PUBLICATIONS_ENDPOINT_PATH_V1 = '/publications/v1';

    /**
     * Creates new {@link Publisher} instance.
     * @param string $channel Publications channel name.
     * @return Publisher
     */
    public function newPublisher(string $channel): Publisher;
}