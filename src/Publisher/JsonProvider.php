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
     * @param array|object $data Source data for JSON.
     * @return string Created JSON.
     * @throws StreamxClientException
     */
    public function getJson(array|object $data): string;
}