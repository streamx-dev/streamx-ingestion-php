<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Impl\Utils\HttpUtils;
use Streamx\Clients\Ingestion\Publisher\HttpRequester;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;
use Streamx\Clients\Ingestion\Publisher\Message;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use Streamx\Clients\Ingestion\Publisher\SuccessResult;

class RestPublisher extends Publisher
{
    private /*array*/ $headers;
    private /*UriInterface*/ $messageIngestionEndpointUri;
    private /*string*/ $payloadTypeName;
    private /*HttpRequester*/ $httpRequester;
    private /*JsonProvider*/ $jsonProvider;

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
        $this->payloadTypeName = $this->convertToPayloadTypeName($channelSchemaName);
        $this->httpRequester = $httpRequester;
        $this->jsonProvider = $jsonProvider;
    }

    private function convertToPayloadTypeName($channelSchemaName): string
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
        $json = $this->jsonProvider->getJson($message, $this->payloadTypeName);
        $actualHeaders = $message->action == Message::PUBLISH_ACTION
            ? array_merge($this->headers, ['Content-Type' => 'application/json; charset=UTF-8'])
            : $this->headers;

        return $this->httpRequester->executePost($this->messageIngestionEndpointUri, $actualHeaders, $json);
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