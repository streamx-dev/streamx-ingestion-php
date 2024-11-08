<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Testing\Impl;

use Streamx\Clients\Ingestion\Impl\DefaultJsonProvider;
use Streamx\Clients\Ingestion\Publisher\Message;

class CustomTestJsonProvider extends DefaultJsonProvider
{

    public function __construct(private readonly string $customFieldValue)
    {
    }

    public function getJson(Message $message, string $schema): string
    {
        $message->payload['customProperty'] = $this->customFieldValue;
        return parent::getJson($message, $schema);
    }
}