<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Unit\Impl;

use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Tests\Testing\MockServerTestCase;
use Streamx\Clients\Ingestion\Tests\Testing\StreamxResponse;

class RestStreamxClientSchemaTest extends MockServerTestCase
{
    private const PAGES_SCHEMA_URL = '/ingestion/v1/channels/pages/schema';
    private const PAGES_SCHEMA_JSON = '{ "page" : { "field" : "value" } }';

    private const ERRORS_SCHEMA_URL = '/ingestion/v1/channels/errors/schema';
    private const ERRORS_CHANNEL = "errors";

    /** @test */
    public function shouldRetrieveChannelSchema()
    {
        // Given
        self::$server->setResponseOfPath(self::PAGES_SCHEMA_URL,
            StreamxResponse::custom(200, self::PAGES_SCHEMA_JSON));

        // When
        $result = $this->createPagesPublisher()->getSchema();

        // Then
        $this->assertSchemaRequest(self::$server->getLastRequest(),
            self::PAGES_SCHEMA_URL,
        );

        $this->assertEquals(self::PAGES_SCHEMA_JSON, $result);
    }

    /** @test */
    public function shouldRetrieveChannelSchemaWithAuthorizationToken()
    {
        // Given
        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())
            ->setAuthToken('abc-100')
            ->build();

        self::$server->setResponseOfPath(self::PAGES_SCHEMA_URL,
            StreamxResponse::custom(200, self::PAGES_SCHEMA_JSON));

        // When
        $result = $this->createPagesPublisher()->getSchema();

        // Then
        $this->assertSchemaRequest(self::$server->getLastRequest(),
            self::PAGES_SCHEMA_URL,
            ['Authorization' => 'Bearer abc-100']
        );

        $this->assertEquals(self::PAGES_SCHEMA_JSON, $result);
    }

    /** @test */
    public function shouldThrowExceptionWhenUnauthorizedError()
    {
        // Given
        self::$server->setResponseOfPath(self::ERRORS_SCHEMA_URL,
            StreamxResponse::custom(401, ''));

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Authentication failed. Make sure that the given token is valid.');

        // When
        $this->createPublisherWithIrrelevantSchema(self::ERRORS_CHANNEL)->getSchema();
    }

    /** @test */
    public function shouldThrowExceptionWhenServerError()
    {
        // Given
        self::$server->setResponseOfPath(self::ERRORS_SCHEMA_URL,
            StreamxResponse::custom(500, ''));

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Communication error. Response status: 500. Message: Internal Server Error');

        // When
        $this->createPublisherWithIrrelevantSchema(self::ERRORS_CHANNEL)->getSchema();
    }

    /** @test */
    public function shouldThrowExceptionWhenNonExistingHost()
    {
        // Given
        $this->client = StreamxClientBuilders::create('https://non-existing')->build();

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('Schema GET request with URI: ' .
            'https://non-existing' . self::ERRORS_SCHEMA_URL . ' failed due to HTTP client error');

        // When
        $this->createPublisherWithIrrelevantSchema(self::ERRORS_CHANNEL)->getSchema();
    }
}