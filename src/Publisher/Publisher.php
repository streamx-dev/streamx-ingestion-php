<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Publisher;

use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;

/**
 * StreamX publications ingestion endpoint contract. `Publisher` instance is reusable.
 */
interface Publisher
{

    /**
     * Performs publications ingestion endpoint `publish` command.
     * @param string $key Publication key.
     * @param array|object $data Publication data.
     * @return SuccessResult
     * @throws StreamxClientException if command failed.
     */
    public function publish(string $key, array|object $data): SuccessResult;

    /**
     * Performs publications ingestion endpoint `unpublish` command.
     * @param string $key Publication key.
     * @return SuccessResult
     * @throws StreamxClientException if command failed.
     */
    public function unpublish(string $key): SuccessResult;
}