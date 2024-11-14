<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Testing;

use donatj\MockWebServer\Response;
use Streamx\Clients\Ingestion\Impl\FailureResponse;
use Streamx\Clients\Ingestion\Publisher\PublisherSuccessResult;

class StreamxResponse
{
    public static function success(int $eventTime): Response
    {
        $successResult = new PublisherSuccessResult($eventTime);
        $json = json_encode($successResult);
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