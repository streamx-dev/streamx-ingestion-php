<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Exceptions;

use Exception;

/**
 * Thrown when client failed command handling due to forbidden channel in the request.
 */
class ForbiddenChannelException extends ServiceFailureException
{

    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}