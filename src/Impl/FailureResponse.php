<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use JsonSerializable;
use stdClass;
use Streamx\Clients\Ingestion\Impl\Utils\DataValidator;

class FailureResponse implements JsonSerializable
{

    public function __construct(
        private readonly string $errorCode,
        private readonly string $errorMessage
    ) {
    }

    public static function fromJson(StdClass $json): FailureResponse
    {
        $dataValidator = DataValidator::for($json);
        return new FailureResponse(
            $dataValidator->require('errorCode'),
            $dataValidator->require('errorMessage'));
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}