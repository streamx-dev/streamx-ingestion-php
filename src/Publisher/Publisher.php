<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Publisher;

use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Impl\MessageStatus;

/**
 * StreamX ingestion endpoint contract. `Publisher` instance is reusable.
 */
abstract class Publisher
{
    /**
     * Performs ingestion endpoint `publish` command.
     * @param string $key Publish key.
     * @param array|object $payload Publish payload.
     * @return SuccessResult containing ingestion endpoint response entity.
     * @throws StreamxClientException if command failed.
     */
    public final function publish(string $key, $payload): SuccessResult
    {
        $message = (Message::newPublishMessage($key, $payload))->build();
        return $this->send($message);
    }

    /**
     * Performs ingestion endpoint `unpublish` command.
     * @param string $key Unpublish key.
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
     * @param Message $message Ingestion message.
     * @return SuccessResult containing ingestion endpoint response entity.
     * @throws StreamxClientException If command filed.
     */
    public abstract function send(Message $message): SuccessResult;

    /**
     * Sends the provided ingestion messages to the Ingestion endpoint.
     * @param Message[] $messages Ingestion messages.
     * @return MessageStatus[] with SuccessResult and/or FailureResponse from ingestion endpoint for each input message (in order).
     * @throws StreamxClientException If a critical error occurred and the MessageStatus[] with SuccessResult and/or FailureResponse cannot be returned.
     */
    public abstract function sendMulti(array $messages): array;
}