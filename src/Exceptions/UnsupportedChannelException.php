<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Exceptions;

/**
 * Thrown when client failed command handling due to unsupported channel in the request.
 */
class UnsupportedChannelException extends ServiceFailureException
{

    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}