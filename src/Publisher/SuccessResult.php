<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Publisher;

use JsonSerializable;
use stdClass;
use Streamx\Clients\Ingestion\Impl\Utils\DataValidator;

/**
 * Model of a successful {@link Publisher} command result.
 */
class SuccessResult implements JsonSerializable
{

    /**
     * Constructs {@link SuccessResult} instance.
     * @param int $eventTime Timestamp of event triggered by publisher command on StreamX.
     * @param string $key Source key of the ingested message.
     */
    public function __construct(
        private readonly int $eventTime,
        private readonly string $key)
    {
    }

    /**
     * @return int Event registration time.
     */
    public function getEventTime(): int
    {
        return $this->eventTime;
    }

    /**
     * @return string Ingestion message key.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public static function fromJson(?StdClass $json): ?SuccessResult
    {
        if ($json == null) {
            return null;
        }
        $dataValidator = DataValidator::for($json);
        return new SuccessResult(
            $dataValidator->require('eventTime'),
            $dataValidator->require('key')
        );
    }
}