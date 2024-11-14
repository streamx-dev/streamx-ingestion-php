<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Unit\Impl;

use donatj\MockWebServer\ResponseStack;
use PHPUnit\Framework\Attributes\Test;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
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
            '{"data":{"name":"Data name","description":"Data description"},' .
            '"property":"<div style=\"margin:20px;\">Data property<\/div>{\"key\":\"value\"}"}');

        $this->assertEquals(123456, $result->getEventTime());
        $this->assertEquals($key, $result->getKey());
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
            '{"content":{"bytes":"Text"}}');

        $this->assertEquals(100232, $result->getEventTime());
        $this->assertEquals($key, $result->getKey());
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
            $key
        );

        $this->assertEquals(100205, $result->getEventTime());
        $this->assertEquals($key, $result->getKey());
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
            '{"name":"Test name","description":null}',
            ['Authorization' => 'Bearer abc-100']);

        $this->assertUnpublishPostRequest(self::$server->getLastRequest(),
            '/ingestion/v1/channels/pages/messages',
            $unpublishKey,
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
            '{"message":"\u00a1Hola, \ud83c\udf0d!"}');

        $this->assertEquals(100298, $result->getEventTime());
        $this->assertEquals($key, $result->getKey());
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
    public function shouldThrowExceptionWhenNotSupportedChannel()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/ingestion/v1/channels/not-supported/messages',
            StreamxResponse::failure(400, 'UNSUPPORTED_TYPE', 'Unsupported type: not-supported'));

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Ingestion REST endpoint known error. ' .
            'Code: UNSUPPORTED_TYPE. Message: Unsupported type: not-supported');

        // When
        $this->createPublisherWithIrrelevantSchema("not-supported")->publish("key", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenNotSupportedChannelWhileUnpublishing()
    {
        // Given
        self::$server->setResponseOfPath('/ingestion/v1/channels/not-supported/channel',
            StreamxResponse::failure(400, 'UNSUPPORTED_TYPE', 'Unsupported type: not-supported'));

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Ingestion REST endpoint known error. ' .
            'Code: UNSUPPORTED_TYPE. Message: Unsupported type: not-supported');

        // When
        $this->createPublisherWithIrrelevantSchema("not-supported")->unpublish("key");
    }

    #[Test]
    public function shouldThrowExceptionWhenServerError()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/ingestion/v1/channels/errors/messages',
            StreamxResponse::failure(500, 'SERVER_ERROR', 'Unexpected server error'));

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Ingestion REST endpoint known error. ' .
            'Code: SERVER_ERROR. Message: Unexpected server error');

        // When
        $this->createPublisherWithIrrelevantSchema("errors")->publish("500", $data);
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
