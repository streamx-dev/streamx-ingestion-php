<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Testing\Model;

class Page
{
    public Content $content;

    public function __construct(Content $content)
    {
        $this->content = $content;
    }
}