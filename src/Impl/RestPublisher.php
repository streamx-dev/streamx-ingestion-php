<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientExceptionFactory;
use Streamx\Clients\Ingestion\Impl\Utils\HttpUtils;
use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;
use Streamx\Clients\Ingestion\Publisher\Message;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use Streamx\Clients\Ingestion\Publisher\SuccessResult;

class RestPublisher extends Publisher
{
    private array $headers;
    private UriInterface $messageIngestionEndpointUri;
    private string $payloadTypeName;
    private HttpRequester $httpRequester;
    private JsonProvider $jsonProvider;

    public function __construct(
        UriInterface $ingestionEndpointUri,
        string $channel,
        string $channelSchemaName,
        ?string $authToken,
        HttpRequester $httpRequester,
        JsonProvider $jsonProvider
    ) {
        $this->headers = $this->buildHttpHeaders($authToken);
        $this->messageIngestionEndpointUri = $this->buildMessageIngestionUri($ingestionEndpointUri, $channel);
        $this->payloadTypeName = self::convertToPayloadTypeName($channelSchemaName);
        $this->httpRequester = $httpRequester;
        $this->jsonProvider = $jsonProvider;
    }

    private static function convertToPayloadTypeName($channelSchemaName): string
    {
        $payloadTypeName = preg_replace('/IngestionMessage$/', '', $channelSchemaName);
        if ($payloadTypeName == $channelSchemaName)
        {
            throw new InvalidArgumentException("Expected the provided channel schema name '$channelSchemaName' to end with 'IngestionMessage'");
        }
        return $payloadTypeName;
    }

    public function send(Message $message): SuccessResult
    {
        $messageStatus = $this->sendMulti([$message])[0];

        if ($messageStatus->getSuccess() != null) {
            return $messageStatus->getSuccess();
        }

        $failureResponse = $messageStatus->getFailure();
        throw StreamxClientExceptionFactory::create(
            $failureResponse->getErrorCode(),
            $failureResponse->getErrorMessage()
        );
    }

    public function sendMulti(array $messages): array
    {
        $multiMessageJson = '';
        foreach ($messages as $message) {
            $multiMessageJson .= $this->jsonProvider->getJson($message, $this->payloadTypeName);
        }

        $actualHeaders = array_merge($this->headers, ['Content-Type' => 'application/json; charset=UTF-8']);
        return $this->httpRequester->executePost($this->messageIngestionEndpointUri, $actualHeaders, $multiMessageJson);
    }

    private function buildHttpHeaders(?string $authToken): array
    {
        if (empty($authToken)) {
            return [];
        }
        return ['Authorization' => 'Bearer ' . $authToken];
    }

    /**
     * @throws StreamxClientException
     */
    private function buildMessageIngestionUri(UriInterface $ingestionEndpointUri, string $channel): UriInterface
    {
        return HttpUtils::buildUri("$ingestionEndpointUri/channels/$channel/messages");
    }
}