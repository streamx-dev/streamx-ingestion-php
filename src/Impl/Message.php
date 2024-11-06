<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

class Message
{

    public function __construct(
        // json_encode function requires properties to be public
        public string $key,
        public string $action,
        public ?int $eventTime,
        public array $properties,
        public array|object|null $payload
    ) {
    }

    public static function newPublishMessage(string $key, array|object $payload): Message
    {
        return new Message(
            $key,
            'publish',
            null,
            [],
            $payload
        );
    }

    public static function newUnpublishMessage(string $key): Message
    {
        return new Message(
            $key,
            'unpublish',
            null,
            [],
            null
        );
    }
}