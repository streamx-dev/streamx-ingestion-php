<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use JsonSerializable;
use stdClass;
use Streamx\Clients\Ingestion\Impl\Utils\DataValidator;

class FailureResponse implements JsonSerializable
{
    private /*string*/ $errorCode;
    private /*string*/ $errorMessage;

    public function __construct(string $errorCode, string $errorMessage)
    {
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
    }

    public static function fromJson(?StdClass $json): ?FailureResponse
    {
        if ($json == null) {
            return null;
        }
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