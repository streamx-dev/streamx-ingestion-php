<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Testing\Model;

class Content
{
    public function __construct(public string $bytes) { }
}