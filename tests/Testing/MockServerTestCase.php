<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Testing;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\RequestInfo;
use PHPUnit\Framework\TestCase;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use Streamx\Clients\Ingestion\StreamxClient;

class MockServerTestCase extends TestCase
{
    protected static MockWebServer $server;
    protected static string $pagesSchemaJson;
    protected static string $dummySchemaJson;

    protected StreamxClient $client;

    public static function setUpBeforeClass(): void
    {
        self::$server = new MockWebServer();
        self::$server->start();
        self::$server->setDefaultResponse(StreamxResponse::success(-1, 'any'));
        self::$pagesSchemaJson = file_get_contents('tests/resources/pages-schema.avsc');
        self::$dummySchemaJson = file_get_contents('tests/resources/dummy-schema.avsc');
    }

    protected function setUp(): void
    {
        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())->build();
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }

    protected function createPagesPublisher() : Publisher
    {
        return $this->client->newPublisher("pages", self::$pagesSchemaJson);
    }

    protected function createPublisherWithIrrelevantSchema(string $channel) : Publisher
    {
        return $this->client->newPublisher($channel, self::$dummySchemaJson);
    }

    protected function assertPublishPostRequest(
        RequestInfo $request,
        string $uri,
        string $key,
        string $payload,
        array $headers = null
    ): void {
        $expectedBody = '{"key":"'.$key.'","action":"publish","eventTime":null,"properties":{},"payload":{"dev.streamx.data.model.Page":'.$payload.'}}';
        $this->assertIngestionPostRequest($request, $uri, $expectedBody, $headers);
        $this->assertEquals('application/json; charset=UTF-8', $request->getHeaders()['Content-Type']);
    }

    protected function assertUnpublishPostRequest(
        RequestInfo $request,
        string $uri,
        string $key,
        array $headers = null
    ): void {
        $expectedBody = '{"key":"'.$key.'","action":"unpublish","eventTime":null,"properties":{},"payload":null}';
        $this->assertIngestionPostRequest($request, $uri, $expectedBody, $headers);
        $this->assertArrayNotHasKey('Content-Type', $request->getHeaders());
    }

    private function assertIngestionPostRequest(
        RequestInfo $request,
        string $uri,
        string $expectedBody,
        array $headers = null
    ): void {
        $this->assertEquals('POST', $request->getRequestMethod());
        $this->assertEquals($uri, $request->getRequestUri());
        $this->assertEquals($expectedBody, $request->getInput());
        $this->assertHeaders($request, $headers);
    }

    protected function assertHeaders(RequestInfo $request, ?array $headers): void
    {
        if ($headers == null) {
            return;
        }
        foreach ($headers as $name => $value) {
            $this->assertEquals($value, $request->getHeaders()[$name]);
        }
    }
}