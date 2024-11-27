<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Exceptions;

/**
 * Thrown when client failed command handling due to error returned by ingestion service.
 */
class ServiceFailureException extends StreamxClientException
{

    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}