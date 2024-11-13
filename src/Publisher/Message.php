<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Publisher;

class Message
{
    public const PUBLISH_ACTION = "publish";
    public const UNPUBLISH_ACTION = "unpublish";

    public string $key;
    public string $action;
    public ?object $eventTime; // null or object {'long': eventTime}
    public object $properties; // no Map type in php. Array is serialized by json_encode with square braces. Use object to receive serializing the properties in curly braces
    public /*array|object|null*/ $payload;

    public function __construct(string $key, string $action, ?int $eventTime, object $properties, $payload)
    {
        $this->key = $key;
        $this->action = $action;
        $this->eventTime = self::wrapEventTime($eventTime);
        $this->properties = $properties;
        $this->payload = $payload;
    }

    private static function wrapEventTime(?int $eventTime): ?object
    {
        if ($eventTime == null) {
            return null;
        }
        $arr = array('long' => $eventTime); // use intermediate array due to "long" being a reserved name
        return (object) $arr;
    }

    public static function newPublishMessage(string $key, $payload): MessageBuilder
    {
        return (new MessageBuilder($key, self::PUBLISH_ACTION))
            ->withPayload($payload);
    }

    public static function newUnpublishMessage(string $key): MessageBuilder
    {
        return new MessageBuilder($key, self::UNPUBLISH_ACTION);
    }
}

class MessageBuilder
{
    public string $key;
    public string $action;
    public ?int $eventTime;
    public object $properties;
    public /*array|object|null*/ $payload;

    public function __construct(string $key, string $action)
    {
        $this->key = $key;
        $this->action = $action;
        $this->eventTime = null;
        $this->properties = (object) [];
        $this->payload = null;
    }

    public function withEventTime(int $eventTime): self
    {
        $this->eventTime = $eventTime;
        return $this;
    }

    public function withProperties(array $properties): self
    {
        $mergedProperties = array_merge((array) $this->properties, $properties);
        $this->properties = (object) $mergedProperties;
        return $this;
    }

    public function withProperty(string $key, string $value): self
    {
        return $this->withProperties([$key => $value]);
    }

    public function withPayload($payload): self
    {
        $this->payload = $payload;
        return $this;
    }

    public function build(): Message
    {
        return new Message(
            $this->key,
            $this->action,
            $this->eventTime,
            $this->properties,
            $this->payload
        );
    }
}