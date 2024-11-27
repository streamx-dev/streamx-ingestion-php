<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Exceptions;

abstract class StreamxClientExceptionFactory
{

    public static function create(string $errorCode, string $errorMessage): StreamxClientException
    {
        $exceptionMessage = self::generateExceptionMessage($errorCode, $errorMessage);

        if ($errorCode == 'UNSUPPORTED_CHANNEL') {
            return new UnsupportedChannelException($exceptionMessage);
        }
        if ($errorCode == 'FORBIDDEN_CHANNEL') {
            return new ForbiddenChannelException($exceptionMessage);
        }
        if ($errorCode == 'INVALID_INGESTION_INPUT') {
            return new IngestionInputInvalidException($exceptionMessage);
        }
        if ($errorCode == 'SERVER_ERROR') {
            return new ServerErrorException($exceptionMessage);
        }
        if ($errorCode == 'SENDING_EVENT_ERROR') {
            return new SendingEventErrorException($exceptionMessage);
        }
        return new ServiceFailureException($exceptionMessage);
    }

    private static function generateExceptionMessage(string $errorCode, string $errorMessage): string
    {
        return sprintf(
            "Ingestion REST endpoint known error. Code: %s. Message: %s",
            $errorCode, $errorMessage
        );
    }
}
