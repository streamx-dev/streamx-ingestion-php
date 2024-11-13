<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;
use Streamx\Clients\Ingestion\Publisher\Message;

class DefaultJsonProvider implements JsonProvider
{

    public function getJson(Message $message, string $payloadTypeName): string
    {
        $this->wrapPayloadWithTypeName($message, $payloadTypeName);

        $messageAsJson = json_encode($message);
        if (json_last_error() == JSON_ERROR_NONE) {
            return $messageAsJson;
        }
        throw new StreamxClientException('JSON encoding error: ' . json_last_error_msg());
    }

    private function wrapPayloadWithTypeName(Message $message, string $payloadTypeName): void
    {
        $payload = $message->payload;
        if ($payload != null) {
            $payload = array($payloadTypeName => $payload);
            $message->payload = $payload;
        }
    }
}