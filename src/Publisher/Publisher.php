<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Publisher;

use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;

/**
 * StreamX ingestion endpoint contract. `Publisher` instance is reusable.
 */
abstract class Publisher
{
    /**
     * Performs ingestion endpoint `publish` command.
     * @param string $key Publish key.
     * @param array|object $payload Publish payload.
     * @param array $additionalRequestOptions Additional request options. Optional.
     *     With default implementation, the supported options are: https://docs.guzzlephp.org/en/stable/request-options.html
     * @return SuccessResult containing ingestion endpoint response entity.
     * @throws StreamxClientException if command failed.
     */
    public final function publish(string $key, $payload, array $additionalRequestOptions = []): SuccessResult
    {
        $message = (Message::newPublishMessage($key, $payload))->build();
        return $this->send($message, $additionalRequestOptions);
    }

    /**
     * Performs ingestion endpoint `unpublish` command.
     * @param string $key Unpublish key.
     * @param array $additionalRequestOptions Additional request options. Optional.
     *     With default implementation, the supported options are: https://docs.guzzlephp.org/en/stable/request-options.html
     * @return SuccessResult containing ingestion endpoint response entity.
     * @throws StreamxClientException if command failed.
     */
    public final function unpublish(string $key, array $additionalRequestOptions = []): SuccessResult
    {
        $message = (Message::newUnpublishMessage($key))->build();
        return $this->send($message, $additionalRequestOptions);
    }

    /**
     * Sends the provided ingestion message to the Ingestion endpoint.
     * @param Message $message Ingestion message.
     * @param array $additionalRequestOptions Additional request options. Optional.
     *    With default implementation, the supported options are: https://docs.guzzlephp.org/en/stable/request-options.html
     * @return SuccessResult containing ingestion endpoint response entity.
     * @throws StreamxClientException If command filed.
     */
    public abstract function send(Message $message, array $additionalRequestOptions = []): SuccessResult;

    /**
     * Sends the provided ingestion messages to the Ingestion endpoint.
     * @param Message[] $messages Ingestion messages.
     * @param array $additionalRequestOptions Additional request options. Optional.
     *    With default implementation, the supported options are: https://docs.guzzlephp.org/en/stable/request-options.html
     * @return MessageStatus[] with SuccessResult and/or FailureResponse from ingestion endpoint for each input message (in order).
     * @throws StreamxClientException If a critical error occurred and the MessageStatus[] with SuccessResult and/or FailureResponse cannot be returned.
     */
    public abstract function sendMulti(array $messages, array $additionalRequestOptions = []): array;
}