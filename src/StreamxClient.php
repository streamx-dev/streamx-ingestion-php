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
    public const INGESTION_ENDPOINT_PATH_V1 = '/ingestion/v1';

    /**
     * Creates new {@link Publisher} instance.
     * @param string $channel Ingestion channel name.
     * @param string $channelSchemaJson Ingestion channel schema.
     * @return Publisher
     */
    public function newPublisher(string $channel, string $channelSchemaJson): Publisher;
}