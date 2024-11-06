<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Testing;

use donatj\MockWebServer\Response;
use Streamx\Clients\Ingestion\Impl\FailureResponse;
use Streamx\Clients\Ingestion\Impl\MessageStatus;
use Streamx\Clients\Ingestion\Publisher\SuccessResult;

class StreamxResponse
{
    public static function success(int $eventTime, string $key): Response
    {
        $successResult = new SuccessResult($eventTime, $key);
        $messageStatus = MessageStatus::ofSuccess($successResult);
        $json = json_encode($messageStatus);
        return new Response($json, [], 202);
    }

    public static function failure(
        int $statusCode,
        string $errorCode,
        string $errorMessage
    ): Response {
        $failureResponse = new FailureResponse($errorCode, $errorMessage);
        $json = json_encode($failureResponse);
        return self::custom($statusCode, $json, ['Content-Type' => 'application/json']);
    }

    public static function custom(int $statusCode, string $body, array $headers = []): Response
    {
        return new Response($body, $headers, $statusCode);
    }
}