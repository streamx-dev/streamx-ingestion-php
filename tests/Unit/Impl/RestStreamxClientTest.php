<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Unit\Impl;

use donatj\MockWebServer\ResponseStack;
use PHPUnit\Framework\Attributes\Test;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Exceptions\ForbiddenChannelException;
use Streamx\Clients\Ingestion\Exceptions\IngestionInputInvalidException;
use Streamx\Clients\Ingestion\Exceptions\SendingEventErrorException;
use Streamx\Clients\Ingestion\Exceptions\ServerErrorException;
use Streamx\Clients\Ingestion\Exceptions\ServiceFailureException;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Exceptions\UnsupportedChannelException;
use Streamx\Clients\Ingestion\Publisher\Message;
use Streamx\Clients\Ingestion\Tests\Testing\MockServerTestCase;
use Streamx\Clients\Ingestion\Tests\Testing\StreamxResponse;

class RestStreamxClientTest extends MockServerTestCase
{
    private const LAST_REQUEST_OFFSET = -1;

    #[Test]
    public function shouldPublishData()
    {
        // Given
        $key = "key-to-publish";
        $data = new NestedData(new Data('Data name', 'Data description'),
            '<div style="margin:20px;">Data property</div>{"key":"value"}');

        self::$server->setResponseOfPath('/ingestion/v1/channels/pages/messages',
            StreamxResponse::success(123456, $key));

        // When
        $result = $this->createPagesPublisher()->publish($key, $data);

        // Then
        $this->assertPublishPostRequest(self::$server->getLastRequest(),
            '/ingestion/v1/channels/pages/messages',
            $key,
            $this->defaultPublishMessageJson($key,
                '{"data":{"name":"Data name","description":"Data description"},' .
                '"property":"<div style=\"margin:20px;\">Data property<\/div>{\"key\":\"value\"}"}'));

        $this->assertSuccessResult($result, 123456, $key);
    }

    #[Test]
    public function shouldPublishDataAsAnArray()
    {
        // Given
        $key = "data-as-array";
        $data = ['content' => ['bytes' => 'Text']];

        self::$server->setResponseOfPath('/ingestion/v1/channels/pages/messages',
            StreamxResponse::success(100232, $key));

        // When
        $result = $this->createPagesPublisher()->publish($key, $data);

        // Then
        $this->assertPublishPostRequest(self::$server->getLastRequest(),
            '/ingestion/v1/channels/pages/messages',
            $key,
            $this->defaultPublishMessageJson($key, '{"content":{"bytes":"Text"}}'));

        $this->assertSuccessResult($result, 100232, $key);
    }

    #[Test]
    public function shouldPublishDataAsMessage()
    {
        // Given
        $key = "key-to-publish";
        $data = new NestedData(new Data('Data name', 'Data content'), 'Nested data content');
        $message = (Message::newPublishMessage($key, $data))
            ->withProperty('key-1', 'value-1')
            ->withEventTime(951)
            ->withProperties(['key-2' => 'value-2', 'key-3' => 'value-3']) // expecting this call to not overwrite previously set properties
            ->withProperty('key-4', 'value-4')
            ->build();

        self::$server->setResponseOfPath('/ingestion/v1/channels/pages/messages',
            StreamxResponse::success(123456, $key));

        // When
        $result = $this->createPagesPublisher()->send($message);

        // Then
        $this->assertPublishPostRequest(self::$server->getLastRequest(),
            '/ingestion/v1/channels/pages/messages',
            $key,
            '{'.
                '"key":"key-to-publish",'.
                '"action":"publish",'.
                '"eventTime":951,'.
                '"properties":{'.
                    '"key-1":"value-1",'.
                    '"key-2":"value-2",'.
                    '"key-3":"value-3",'.
                    '"key-4":"value-4"'.
                '},'.
                '"payload":{'.
                    '"dev.streamx.data.model.Page":{'.
                        '"data":{"'.
                            'name":"Data name",'.
                            '"description":"Data content"'.
                        '},'.
                        '"property":"Nested data content"'.
                    '}'.
                '}'.
            '}'
        );

        $this->assertSuccessResult($result, 123456, $key);
    }

    #[Test]
    public function shouldUnpublishData()
    {
        // Given
        $key = "key-to-unpublish";
        self::$server->setResponseOfPath('/ingestion/v1/channels/pages/messages',
            StreamxResponse::success(100205, $key));

        // When
        $result = $this->createPagesPublisher()->unpublish($key);

        // Then
        $this->assertUnpublishPostRequest(self::$server->getLastRequest(),
            '/ingestion/v1/channels/pages/messages',
            $key,
            $this->defaultUnpublishMessageJson($key),
        );

        $this->assertSuccessResult($result, 100205, $key);
    }

    #[Test]
    public function shouldUnpublishDataAsMessage()
    {
        // Given
        $key = "key-to-unpublish";
        $message = (Message::newUnpublishMessage($key))
            ->withProperty('key-1', 'value-1')
            ->withEventTime(951)
            ->withProperties(['key-2' => 'value-2', 'key-3' => 'value-3']) // expecting this call to not overwrite previously set properties
            ->withProperty('key-4', 'value-4')
            ->build();

            self::$server->setResponseOfPath('/ingestion/v1/channels/pages/messages',
            StreamxResponse::success(100205, $key));

        // When
        $result = $this->createPagesPublisher()->send($message);

        // Then
        $this->assertUnpublishPostRequest(self::$server->getLastRequest(),
            '/ingestion/v1/channels/pages/messages',
            $key,
            '{'.
                '"key":"key-to-unpublish",'.
                '"action":"unpublish",'.
                '"eventTime":951,'.
                '"properties":{'.
                    '"key-1":"value-1",'.
                    '"key-2":"value-2",'.
                    '"key-3":"value-3",'.
                    '"key-4":"value-4"'.
                '},'.
                '"payload":null'.
            '}'
        );

        $this->assertSuccessResult($result, 100205, $key);
    }

    #[Test]
    public function shouldHandleSuccessResponseWithUnknownProperty()
    {
        // Given
        $key = "key-to-publish";
        $data = new Data('name', 'content');
        $message = (Message::newPublishMessage($key, $data))->build();

        self::$server->setResponseOfPath('/ingestion/v1/channels/pages/messages',
            StreamxResponse::custom(
                202,
                '{'.
                    '"success":{"eventTime":123456,"key":"'.$key.'","unknownPropertyKey":"unknownPropertyValue"},'.
                    '"failure":null'.
                '}'
            )
        );

        // When
        $result = $this->createPagesPublisher()->send($message);

        // Then
        $this->assertPublishPostRequest(self::$server->getLastRequest(),
            '/ingestion/v1/channels/pages/messages',
            $key,
            $this->defaultPublishMessageJson($key, '{"name":"name","description":"content"}'));

        $this->assertSuccessResult($result, 123456, $key);
    }

    #[Test]
    public function shouldMakeIngestionsWithAuthorizationToken()
    {
        // Given
        $publishKey = "publish-with-token";
        $unpublishKey = "unpublish-with-token";
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/ingestion/v1/channels/pages/messages',
            new ResponseStack(
                StreamxResponse::success(100211, $publishKey),
                StreamxResponse::success(100212, $unpublishKey)));

        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())
            ->setAuthToken('abc-100')
            ->build();

        // When
        $this->createPagesPublisher()->publish($publishKey, $data);
        $this->createPagesPublisher()->unpublish($unpublishKey);

        // Then
        $this->assertPublishPostRequest(self::$server->getRequestByOffset($this::LAST_REQUEST_OFFSET - 1),
            '/ingestion/v1/channels/pages/messages',
            $publishKey,
            $this->defaultPublishMessageJson($publishKey, '{"name":"Test name","description":null}'),
            ['Authorization' => 'Bearer abc-100']);

        $this->assertUnpublishPostRequest(self::$server->getLastRequest(),
            '/ingestion/v1/channels/pages/messages',
            $unpublishKey,
            $this->defaultUnpublishMessageJson($unpublishKey),
            ['Authorization' => 'Bearer abc-100']);
    }

    #[Test]
    public function shouldSendStringInUtf8Encoding()
    {
        // Given
        $key = "utf8";
        $data = ['message' => '¡Hola, 🌍!'];

        self::$server->setResponseOfPath('/ingestion/v1/channels/pages/messages',
            StreamxResponse::success(100298, $key));

        // When
        $result = $this->createPagesPublisher()->publish($key, $data);

        // Then
        $this->assertPublishPostRequest(self::$server->getLastRequest(),
            '/ingestion/v1/channels/pages/messages',
            $key,
            $this->defaultPublishMessageJson($key, '{"message":"\u00a1Hola, \ud83c\udf0d!"}'));

        $this->assertSuccessResult($result, 100298, $key);
    }

    #[Test]
    public function shouldThrowExceptionWhenDataNotInUtf8Encoding()
    {
        // Given
        $data = ['message' => mb_convert_encoding('¡Hola, 🌍!', 'ISO-8859-1', 'UTF-8')];

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('JSON encoding error: Malformed UTF-8 characters, possibly incorrectly encoded');

        // When
        $this->createPagesPublisher()->publish("latin-1", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenMessagePropertyNotInUtf8Encoding()
    {
        // Given
        $key = 'latin-1';
        $data = new Data('some data');
        $message = (Message::newPublishMessage($key, $data))
            ->withProperty('property-name', mb_convert_encoding('¡Hola, 🌍!', 'ISO-8859-1', 'UTF-8'))
            ->build();

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('JSON encoding error: Malformed UTF-8 characters, possibly incorrectly encoded');

        // When
        $this->createPagesPublisher()->send($message);
    }

    #[Test]
    public function shouldThrowExceptionWhenInvalidIngestionInput()
    {
        // Given
        $channel = "pages";
        $data = new Data('Test name');

        self::$server->setResponseOfPath("/ingestion/v1/channels/$channel/messages",
            StreamxResponse::failure(400, 'INVALID_INGESTION_INPUT', 'Invalid data.'));

        // Expect exception
        $this->expectException(IngestionInputInvalidException::class);
        $this->expectExceptionMessage('Ingestion REST endpoint known error. ' .
            'Code: INVALID_INGESTION_INPUT. Message: Invalid data.');

        // When
        $this->createPublisherWithIrrelevantSchema($channel)->publish("key", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenForbiddenChannel()
    {
        // Given
        $channel = "administration";
        $data = new Data('Test name');

        self::$server->setResponseOfPath("/ingestion/v1/channels/$channel/messages",
            StreamxResponse::failure(403, 'FORBIDDEN_CHANNEL', "Forbidden channel: $channel"));

        // Expect exception
        $this->expectException(ForbiddenChannelException::class);
        $this->expectExceptionMessage('Ingestion REST endpoint known error. ' .
            "Code: FORBIDDEN_CHANNEL. Message: Forbidden channel: $channel");

        // When
        $this->createPublisherWithIrrelevantSchema($channel)->publish("key", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenNotSupportedChannel()
    {
        // Given
        $channel = "assets";
        $data = new Data('Test name');

        self::$server->setResponseOfPath("/ingestion/v1/channels/$channel/messages",
            StreamxResponse::failure(400, 'UNSUPPORTED_CHANNEL', "Unsupported channel: $channel"));

        // Expect exception
        $this->expectException(UnsupportedChannelException::class);
        $this->expectExceptionMessage('Ingestion REST endpoint known error. ' .
            "Code: UNSUPPORTED_CHANNEL. Message: Unsupported channel: $channel");

        // When
        $this->createPublisherWithIrrelevantSchema($channel)->publish("key", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenNotSupportedChannelWhileUnpublishing()
    {
        // Given
        $channel = "assets";
        self::$server->setResponseOfPath("/ingestion/v1/channels/$channel/messages",
            StreamxResponse::failure(400, 'UNSUPPORTED_CHANNEL', "Unsupported channel: $channel"));

        // Expect exception
        $this->expectException(UnsupportedChannelException::class);
        $this->expectExceptionMessage('Ingestion REST endpoint known error. ' .
            "Code: UNSUPPORTED_CHANNEL. Message: Unsupported channel: $channel");

        // When
        $this->createPublisherWithIrrelevantSchema($channel)->unpublish("key");
    }

    #[Test]
    public function shouldThrowExceptionWhenSendingEventError()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/ingestion/v1/channels/errors/messages',
            StreamxResponse::failure(500, 'SENDING_EVENT_ERROR', 'Error sending event'));

        // Expect exception
        $this->expectException(SendingEventErrorException::class);
        $this->expectExceptionMessage('Ingestion REST endpoint known error. ' .
            'Code: SENDING_EVENT_ERROR. Message: Error sending event');

        // When
        $this->createPublisherWithIrrelevantSchema("errors")->publish("500", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenUnexpectedServerError()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/ingestion/v1/channels/errors/messages',
            StreamxResponse::failure(500, 'SERVER_ERROR', 'Unexpected server error'));

        // Expect exception
        $this->expectException(ServerErrorException::class);
        $this->expectExceptionMessage('Ingestion REST endpoint known error. ' .
            'Code: SERVER_ERROR. Message: Unexpected server error');

        // When
        $this->createPublisherWithIrrelevantSchema("errors")->publish("key", $data);
    }

    #[Test]
    public function shouldThrowServiceFailureExceptionWhenUnknownServersideErrorCode()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/ingestion/v1/channels/errors/messages',
            StreamxResponse::failure(500, 'SOME_ERROR_CODE', 'Something happened'));

        // Expect exception
        $this->expectException(ServiceFailureException::class);
        $this->expectExceptionMessage('Ingestion REST endpoint known error. ' .
            'Code: SOME_ERROR_CODE. Message: Something happened');

        // When
        $this->createPublisherWithIrrelevantSchema("errors")->publish("key", $data);
    }

    #[Test]
    public function shouldThrowServiceFailureExceptionWhenErrorResponseWithSuccessHttpStatus()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/ingestion/v1/channels/errors/messages',
            StreamxResponse::successResultWithFailure('SOME_ERROR_CODE', 'Something happened'));

        // Expect exception
        $this->expectException(ServiceFailureException::class);
        $this->expectExceptionMessage('Ingestion REST endpoint known error. ' .
            'Code: SOME_ERROR_CODE. Message: Something happened');

        // When
        $this->createPublisherWithIrrelevantSchema("errors")->publish("key", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenInvalidResponseModel()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/ingestion/v1/channels/errors/messages',
            StreamxResponse::custom(500, '{"invalid}": "data"'));

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Communication error. Response status: 500. Message: ' .
            'Response could not be parsed.');

        // When
        $this->createPublisherWithIrrelevantSchema("errors")->publish("500", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenUnknownResponseModel()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/ingestion/v1/channels/errors/messages',
            StreamxResponse::custom(500, '{"correct-json":"but unknown model"}'));

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Communication error. Response status: 500. Message: ' .
            'Property [errorCode] is required');

        // When
        $this->createPublisherWithIrrelevantSchema("errors")->publish("500", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenUndefinedServerError()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/ingestion/v1/channels/errors/messages',
            StreamxResponse::custom(408, 'Request to this server timed out'));

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Communication error. Response status: 408. Message: Request Timeout');

        // When
        $this->createPublisherWithIrrelevantSchema("errors")->publish("408", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenUnauthorizedError()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/ingestion/v1/channels/errors/messages',
            StreamxResponse::custom(401, ''));

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Authentication failed. Make sure that the given token is valid.');

        // When
        $this->createPublisherWithIrrelevantSchema("errors")->publish("401", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenNonExistingHost()
    {
        // Given
        $data = new Data('Test name');

        $this->client = StreamxClientBuilders::create('https://non-existing')->build();

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('POST request with URI: ' .
            'https://non-existing/ingestion/v1/channels/errors/messages failed due to HTTP client error');

        // When
        $this->createPublisherWithIrrelevantSchema("errors")->publish("non-existing-host", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenNonExistingHostWhileUnpublishing()
    {
        // Given
        $this->client = StreamxClientBuilders::create('https://non-existing')->build();

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('POST request with URI: ' .
            'https://non-existing/ingestion/v1/channels/errors/messages failed due to HTTP client error');

        // When
        $this->createPublisherWithIrrelevantSchema("errors")->unpublish("non-existing-host");
    }

    #[Test]
    public function shouldThrowExceptionWhenRelativeUrl()
    {
        // Given
        $serverUrl = 'relative/url';

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Ingestion endpoint URI: relative/url/ingestion/v1 is malformed. ' .
            'Relative URI is not supported.');

        // When
        $this->client = StreamxClientBuilders::create($serverUrl)->build();
    }

    #[Test]
    public function shouldThrowExceptionWhenUrlWithoutHost()
    {
        // Given
        $serverUrl = 'https:///url-without-host';

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Ingestion endpoint URI: https:///url-without-host/ingestion/v1 is malformed. ' .
            'Unable to parse URI: https:///url-without-host/ingestion/v1');

        // When
        $this->client = StreamxClientBuilders::create($serverUrl)->build();
    }

    #[Test]
    public function shouldThrowExceptionWhenMalformedUrl()
    {
        // Given
        $serverUrl = ':malformed-uri/path';

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Ingestion endpoint URI: :malformed-uri/path/ingestion/v1 is malformed. ' .
            'Relative URI is not supported.');

        // When
        $this->client = StreamxClientBuilders::create($serverUrl)->build();
    }

    private function assertSuccessResult($response, int $expectedEventTime, string $expectedKey): void
    {
        $this->assertInstanceOf('Streamx\Clients\Ingestion\Publisher\SuccessResult', $response);
        $this->assertEquals($expectedEventTime, $response->getEventTime());
        $this->assertEquals($expectedKey, $response->getKey());
    }
}

class NestedData
{

    public function __construct(
        public Data $data,
        public string $property
    ) {
    }
}

class Data
{

    public function __construct(
        public string $name,
        public ?string $description = null
    ) {
    }

}
