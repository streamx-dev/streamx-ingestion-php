<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;

class DefaultJsonProvider implements JsonProvider
{

    public function getJson(array|object $data): string
    {
        $json = json_encode($data);
        if (json_last_error() == JSON_ERROR_NONE) {
            return $json;
        } else {
            throw new StreamxClientException('JSON encoding error: ' . json_last_error_msg());
        }
    }
}