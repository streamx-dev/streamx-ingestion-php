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
        return self::create202Response($json);
    }

    public static function failure(int $statusCode, string $errorCode, string $errorMessage): Response
    {
        $failureResponse = new FailureResponse($errorCode, $errorMessage);
        $json = json_encode($failureResponse);
        return self::custom($statusCode, $json, ['Content-Type' => 'application/json']);
    }

    public static function successResultWithFailure(string $errorCode, string $errorMessage): Response
    {
        $failureResponse = new FailureResponse($errorCode, $errorMessage);
        $messageStatus = MessageStatus::ofFailure($failureResponse);
        $json = json_encode($messageStatus);
        return self::create202Response($json);
    }

    /**
     * @param $messageResponses array of SuccessResult and/or FailureResponse $messageResponses
     */
    public static function responseForMultipleMessages(array $messageResponses): Response
    {
        $jsons = [];
        foreach ($messageResponses as $messageResponse) {
            if ($messageResponse instanceof SuccessResult) {
                $jsons[] = json_encode(MessageStatus::ofSuccess($messageResponse));
            } else if ($messageResponse instanceof FailureResponse) {
                $jsons[] = json_encode(MessageStatus::ofFailure($messageResponse));
            }
        }
        return self::create202Response(implode("\n", $jsons));
    }

    public static function custom(int $statusCode, string $body, array $headers = []): Response
    {
        return new Response($body, $headers, $statusCode);
    }

    public static function create202Response($body): Response
    {
        return self::custom(202, $body);
    }
}