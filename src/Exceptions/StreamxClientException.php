<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Exceptions;

use Exception;

/**
 * Thrown when client failed to handle command.
 */
class StreamxClientException extends Exception
{

    /**
     * Creates `StreamxClientException` with description and root cause of failure.
     * @param string $message Failure description.
     * @param Exception|null $previous Cause of failure.
     */
    public function __construct(string $message = "", Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}