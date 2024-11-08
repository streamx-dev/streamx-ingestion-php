<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use JsonSerializable;
use stdClass;
use Streamx\Clients\Ingestion\Impl\Utils\DataValidator;
use Streamx\Clients\Ingestion\Publisher\SuccessResult;

class MessageStatus implements JsonSerializable
{

    public function __construct(
        private readonly ?SuccessResult $success,
        private readonly ?FailureResponse $failure
    ) {
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
        $dataValidator = DataValidator::for($json);
        return new MessageStatus(
            SuccessResult::fromJson($dataValidator->retrieveNullable('success')),
            FailureResponse::fromJson($dataValidator->retrieveNullable('failure'))
        );
    }
}