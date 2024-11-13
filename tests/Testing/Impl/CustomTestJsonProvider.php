<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Testing\Impl;

use Streamx\Clients\Ingestion\Impl\DefaultJsonProvider;
use Streamx\Clients\Ingestion\Publisher\Message;

class CustomTestJsonProvider extends DefaultJsonProvider
{
    
    private string $customFieldValue;

    public function __construct(string $customFieldValue)
    {
        $this->customFieldValue = $customFieldValue;
    }

    public function getJson(Message $message, string $payloadTypeName): string
    {
        $message->payload['customProperty'] = $this->customFieldValue;
        return parent::getJson($message, $payloadTypeName);
    }
}