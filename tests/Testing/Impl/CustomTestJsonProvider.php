<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Testing\Impl;

use Streamx\Clients\Ingestion\Publisher\JsonProvider;

class CustomTestJsonProvider implements JsonProvider
{

    public function __construct(private readonly string $customFieldValue)
    {
    }

    public function getJson(object|array $data): string
    {
        $data = (array)$data;
        $data['customProperty'] = $this->customFieldValue;
        return json_encode($data);
    }
}