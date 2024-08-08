<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Unit\Impl;

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
        $data = new NestedData(new Data('Data name', 'Data description'),
            '<div style="margin:20px;">Data property</div>{"key":"value"}');

        self::$server->setResponseOfPath('/publications/v1/channel/key-to-publish',
            StreamxResponse::success(123456));

        // When
        $result = $this->client->newPublisher("channel")->publish("key-to-publish", $data);

        // Then
        $this->assertPut(self::$server->getLastRequest(),
            '/publications/v1/channel/key-to-publish',
            '{"data":{"name":"Data name","description":"Data description"},' .
            '"property":"<div style=\"margin:20px;\">Data property<\/div>{\"key\":\"value\"}"}');

        $this->assertEquals(123456, $result->getEventTime());
    }

    #[Test]
    public function shouldPublishDataAsAnArray()
    {
        // Given
        $data = ['content' => ['bytes' => 'Text']];

        self::$server->setResponseOfPath('/publications/v1/channel/data-as-array',
            StreamxResponse::success(100232));

        // When
        $result = $this->client->newPublisher("channel")->publish("data-as-array", $data);

        // Then
        $this->assertPut(self::$server->getLastRequest(),
            '/publications/v1/channel/data-as-array',
            '{"content":{"bytes":"Text"}}');

        $this->assertEquals(100232, $result->getEventTime());
    }

    #[Test]
    public function shouldUnpublishData()
    {
        // Given
        self::$server->setResponseOfPath('/publications/v1/channel/key-to-unpublish',
            StreamxResponse::success(100205));

        // When
        $result = $this->client->newPublisher("channel")->unpublish("key-to-unpublish");

        // Then
        $this->assertDelete(self::$server->getLastRequest(),
            '/publications/v1/channel/key-to-unpublish');

        $this->assertEquals(100205, $result->getEventTime());
    }

    #[Test]
    public function shouldMakePublicationsWithAuthorizationToken()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/publications/v1/data/publish-with-token',
            StreamxResponse::success(100211));

        self::$server->setResponseOfPath('/publications/v1/data/unpublish-with-token',
            StreamxResponse::success(100212));

        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())
            ->setAuthToken('abc-100')
            ->build();

        // When
        $publisher = $this->client->newPublisher("data");
        $publisher->publish("publish-with-token", $data);
        $publisher->unpublish("unpublish-with-token");

        // Then
        $this->assertPut(self::$server->getRequestByOffset($this::LAST_REQUEST_OFFSET - 1),
            '/publications/v1/data/publish-with-token',
            '{"name":"Test name","description":null}',
            ['Authorization' => 'Bearer abc-100']);

        $this->assertDelete(self::$server->getLastRequest(),
            '/publications/v1/data/unpublish-with-token',
            ['Authorization' => 'Bearer abc-100']);
    }

    #[Test]
    public function shouldSendStringInUtf8Encoding()
    {
        // Given
        $data = ['message' => '¡Hola, 🌍!'];

        self::$server->setResponseOfPath('/publications/v1/channel/utf8',
            StreamxResponse::success(100298));

        // When
        $result = $this->client->newPublisher("channel")->publish("utf8", $data);

        // Then
        $this->assertPut(self::$server->getLastRequest(),
            '/publications/v1/channel/utf8',
            '{"message":"\u00a1Hola, \ud83c\udf0d!"}');

        $this->assertEquals(100298, $result->getEventTime());
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
        $this->client->newPublisher("channel")->publish("latin-1", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenNotSupportedChannel()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/publications/v1/not-supported/key',
            StreamxResponse::failure(400, 'UNSUPPORTED_TYPE', 'Unsupported type: not-supported'));

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Publication Ingestion REST endpoint known error. ' .
            'Code: UNSUPPORTED_TYPE. Message: Unsupported type: not-supported');

        // When
        $this->client->newPublisher("not-supported")->publish("key", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenNotSupportedChannelWhileUnpublishing()
    {
        // Given
        self::$server->setResponseOfPath('/publications/v1/not-supported/key',
            StreamxResponse::failure(400, 'UNSUPPORTED_TYPE', 'Unsupported type: not-supported'));

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Publication Ingestion REST endpoint known error. ' .
            'Code: UNSUPPORTED_TYPE. Message: Unsupported type: not-supported');

        // When
        $this->client->newPublisher("not-supported")->unpublish("key");
    }

    #[Test]
    public function shouldThrowExceptionWhenServerError()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/publications/v1/errors/500',
            StreamxResponse::failure(500, 'SERVER_ERROR', 'Unexpected server error'));

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Publication Ingestion REST endpoint known error. ' .
            'Code: SERVER_ERROR. Message: Unexpected server error');

        // When
        $this->client->newPublisher("errors")->publish("500", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenInvalidResponseModel()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/publications/v1/errors/500',
            StreamxResponse::custom(500, '{"invalid}": "data"'));

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Communication error. Response status: 500. Message: ' .
            'Response could not be parsed.');

        // When
        $this->client->newPublisher("errors")->publish("500", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenUnknownResponseModel()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/publications/v1/errors/500',
            StreamxResponse::custom(500, '{"correct-json":"but unknown model"}'));

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Communication error. Response status: 500. Message: ' .
            'Property [errorCode] is required');

        // When
        $this->client->newPublisher("errors")->publish("500", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenUndefinedServerError()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/publications/v1/errors/408',
            StreamxResponse::custom(408, 'Request to this server timed out'));

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Communication error. Response status: 408. Message: Request Timeout');

        // When
        $this->client->newPublisher("errors")->publish("408", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenUnauthorizedError()
    {
        // Given
        $data = new Data('Test name');

        self::$server->setResponseOfPath('/publications/v1/errors/401',
            StreamxResponse::custom(401, ''));

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Authentication failed. Make sure that the given token is valid.');

        // When
        $this->client->newPublisher("errors")->publish("401", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenNonExistingHost()
    {
        // Given
        $data = new Data('Test name');

        $this->client = StreamxClientBuilders::create('https://non-existing')->build();

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('PUT request with URI: ' .
            'https://non-existing/publications/v1/errors/non-existing-host failed due to HTTP client error');

        // When
        $this->client->newPublisher("errors")->publish("non-existing-host", $data);
    }

    #[Test]
    public function shouldThrowExceptionWhenNonExistingHostWhileUnpublishing()
    {
        // Given
        $this->client = StreamxClientBuilders::create('https://non-existing')->build();

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('DELETE request with URI: ' .
            'https://non-existing/publications/v1/errors/non-existing-host failed due to HTTP client error');

        // When
        $this->client->newPublisher("errors")->unpublish("non-existing-host");
    }

    #[Test]
    public function shouldThrowExceptionWhenRelativeUrl()
    {
        // Given
        $serverUrl = 'relative/url';

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Publication endpoint URI: relative/url/publications/v1 is malformed. ' .
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
        $this->expectExceptionMessage('Publication endpoint URI: https:///url-without-host/publications/v1 is malformed. ' .
            'Unable to parse URI: https:///url-without-host/publications/v1');

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
        $this->expectExceptionMessage('Publication endpoint URI: :malformed-uri/path/publications/v1 is malformed. ' .
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
