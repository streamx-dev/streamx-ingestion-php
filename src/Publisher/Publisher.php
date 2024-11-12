<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Publisher;

use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;

/**
 * StreamX publications ingestion endpoint contract. `Publisher` instance is reusable.
 */
abstract class Publisher
{

    /**
     * Performs publications ingestion endpoint `publish` command.
     * @param string $key Publication key.
     * @param array|object $payload Publication payload.
     * @return SuccessResult containing ingestion endpoint response entity.
     * @throws StreamxClientException if command failed.
     */
    public final function publish(string $key, $payload): SuccessResult
    {
        $message = (Message::newPublishMessage($key, $payload))->build();
        return $this->send($message);
    }

    /**
     * Performs publications ingestion endpoint `unpublish` command.
     * @param string $key Publication key.
     * @return SuccessResult containing ingestion endpoint response entity.
     * @throws StreamxClientException if command failed.
     */
    public final function unpublish(string $key): SuccessResult
    {
        $message = (Message::newUnpublishMessage($key))->build();
        return $this->send($message);
    }

    /**
     * Sends the provided ingestion message to the Ingestion endpoint.
     * @param object $message Ingestion message.
     * @return SuccessResult containing ingestion endpoint response entity.
     * @throws StreamxClientException If command filed.
     */
    public abstract function send(Message $message): SuccessResult;
}