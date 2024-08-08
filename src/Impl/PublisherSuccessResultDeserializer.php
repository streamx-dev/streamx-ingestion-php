<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use stdClass;
use Streamx\Clients\Ingestion\Impl\Utils\DataValidator;
use Streamx\Clients\Ingestion\Publisher\PublisherSuccessResult;

class PublisherSuccessResultDeserializer
{

    public static function fromJson(StdClass $json): PublisherSuccessResult
    {
        return new PublisherSuccessResult((int)DataValidator::for($json)->require('eventTime'));
    }
}