<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Publisher;

use JsonSerializable;
use stdClass;
use Streamx\Clients\Ingestion\Impl\Utils\PropertyRetriever;

class MessageStatus implements JsonSerializable
{

    private ?SuccessResult $success;
    private ?FailureResponse $failure;

    public function __construct(?SuccessResult $success, ?FailureResponse $failure)
    {
        $this->success = $success;
        $this->failure = $failure;
    }

    public static function ofSuccess(SuccessResult $success): MessageStatus
    {
        return new MessageStatus($success, null);
    }

    public static function ofFailure(FailureResponse $failure): MessageStatus
    {
        return new MessageStatus(null, $failure);
    }

    public function getSuccess(): ?SuccessResult
    {
        return $this->success;
    }

    public function getFailure(): ?FailureResponse
    {
        return $this->failure;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public static function fromJson(StdClass $json): MessageStatus
    {
        $propertyRetriever = PropertyRetriever::for($json);
        return new MessageStatus(
            SuccessResult::fromJson($propertyRetriever->retrieveNullable('success')),
            FailureResponse::fromJson($propertyRetriever->retrieveNullable('failure'))
        );
    }
}