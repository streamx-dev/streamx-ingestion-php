<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Exceptions;

use Exception;

/**
 * Thrown when client failed command handling.
 */
class StreamxClientException extends Exception
{

    public function __construct(string $message, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}