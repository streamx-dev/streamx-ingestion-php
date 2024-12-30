<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Unit\Impl;

use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Tests\Testing\MockServerTestCase;
use Streamx\Clients\Ingestion\Tests\Testing\StreamxResponse;

class RestStreamxClientAvailabilityTest extends MockServerTestCase
{
    private const HEALTH_CHECK_URL = '/q/health';
    private const HEALTH_CHECK_UP_RESPONSE = '{ "status": "UP" }';
    private const HEALTH_CHECK_DOWN_RESPONSE = '{ "status": "DOWN" }';

    /** @test */
    public function shouldCheckIsIngestionServiceAvailable()
    {
        // Given
        self::$server->setResponseOfPath(self::HEALTH_CHECK_URL,
            StreamxResponse::custom(200, self::HEALTH_CHECK_UP_RESPONSE));

        // When
        $result = $this->createPagesPublisher()->isIngestionServiceAvailable();

        // Then
        $this->assertTrue($result);
    }

    /** @test */
    public function shouldCheckIsIngestionServiceAvailable_WhenHealthCheckReturnsDown()
    {
        // Given
        self::$server->setResponseOfPath(self::HEALTH_CHECK_URL,
            StreamxResponse::custom(200, self::HEALTH_CHECK_DOWN_RESPONSE));

        // When
        $result = $this->createPagesPublisher()->isIngestionServiceAvailable();

        // Then
        $this->assertFalse($result);
    }

    /** @test */
    public function shouldCheckIsIngestionServiceAvailable_WhenInternalServerError()
    {
        // Given
        self::$server->setResponseOfPath(self::HEALTH_CHECK_URL,
            StreamxResponse::custom(500, ''));

        // When
        $result = $this->createPagesPublisher()->isIngestionServiceAvailable();

        // Then
        $this->assertFalse($result);
    }

    /** @test */
    public function shouldThrowExceptionWhenNonExistingHost()
    {
        // Given
        $this->client = StreamxClientBuilders::create('https://non-existing')->build();

        // Expect exception
        $this->expectException(StreamxClientException::class);
        $this->expectExceptionMessage('HealthCheck GET request with URI: ' .
            'https://non-existing/q/health failed due to HTTP client error');

        // When
        $this->createPublisherWithIrrelevantSchema('any')->isIngestionServiceAvailable();
    }
}