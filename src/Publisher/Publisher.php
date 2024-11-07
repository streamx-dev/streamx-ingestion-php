<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Publisher;

use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Impl\Message;

/**
 * StreamX publications ingestion endpoint contract. `Publisher` instance is reusable.
 */
interface Publisher
{

    /**
     * Performs publications ingestion endpoint `publish` command.
     * @param string $key Publication key.
     * @param array|object $payload Publication payload.
     * @return SuccessResult containing ingestion endpoint response entity.
     * @throws StreamxClientException if command failed.
     */
    public function publish(string $key, array|object $payload): SuccessResult;

    /**
     * Performs publications ingestion endpoint `unpublish` command.
     * @param string $key Publication key.
     * @return SuccessResult containing ingestion endpoint response entity.
     * @throws StreamxClientException if command failed.
     */
    public function unpublish(string $key): SuccessResult;

    /**
     * Sends the provided ingestion message to the Ingestion endpoint.
     * @param object $message Ingestion message.
     * @return SuccessResult containing ingestion endpoint response entity.
     * @throws StreamxClientException If command filed.
     */
    public function send(Message $message): SuccessResult;
}