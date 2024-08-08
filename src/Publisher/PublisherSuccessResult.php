<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Publisher;

use JsonSerializable;

/**
 * Model of a successful {@link Publisher} command result.
 */
class PublisherSuccessResult implements JsonSerializable
{

    /**
     * Constructs {@link PublisherSuccessResult} instance.
     * @param int $eventTime Timestamp of event triggered by publisher command on StreamX.
     */
    public function __construct(private readonly int $eventTime)
    {
    }

    /**
     * @return int Event registration time.
     */
    public function getEventTime(): int
    {
        return $this->eventTime;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}