<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Testing\Model;

class Content
{
    public string $bytes;

    public function __construct(string $bytes)
    {
        $this->bytes = $bytes;
    }
}