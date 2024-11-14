<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Exceptions;

use Exception;

/**
 * Thrown when client failed command handling due to server error.
 */
class ServerErrorException extends ServiceFailureException
{

    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}