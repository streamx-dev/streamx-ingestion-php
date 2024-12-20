<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion;

use Streamx\Clients\Ingestion\Publisher\Publisher;

/**
 * Represents a client that can communicate with StreamX Ingestion Service.
 */
interface StreamxClient
{
    /**
     * StreamX REST Ingestion base path.
     */
    public const INGESTION_ENDPOINT_BASE_PATH = '/ingestion/v1';

    /**
     * Creates new {@link Publisher} instance.
     * @param string $channel Ingestion channel name.
     * @param string $channelSchemaName Fully qualified name of the Ingestion channel schema.
     * @return Publisher
     */
    public function newPublisher(string $channel, string $channelSchemaName): Publisher;

}