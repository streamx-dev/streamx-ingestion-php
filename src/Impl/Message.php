<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

class Message
{

    public function __construct(
        // json_encode function requires properties to be public
        public string $key,
        public string $action,
        public ?int $eventTime,
        public object $properties, // no Map type in php. Array is serialized by json_encode with square braces. Use object to receive serializing the properties in curly braces
        public array|object|null $payload
    ) {
    }

    public static function newPublishMessage(string $key, array|object $payload): Message
    {
        return new Message(
            $key,
            'publish',
            null,
            (object) [],
            $payload
        );
    }

    // TODO add builder methods for setting eventTime and properties, along with unit tests

    public static function newUnpublishMessage(string $key): Message
    {
        return new Message(
            $key,
            'unpublish',
            null,
            (object) [],
            null
        );
    }
}