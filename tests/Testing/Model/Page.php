<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Testing\Model;

class Page
{
    public function __construct(public Content $content) { }
}