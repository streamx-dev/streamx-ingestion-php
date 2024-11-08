<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Publisher;

use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;

/**
 * An interface that allows to inject custom JSON Provider.
 */
interface JsonProvider
{
    /**
     * Generates JSON string from input data.
     * @param Message $message Source ingestion message.
     * @param string $schemaJson Schema to use for serializing the message to JSON.
     * @return string Created JSON.
     * @throws StreamxClientException
     */
    public function getJson(Message $message, string $schemaJson): string;
}