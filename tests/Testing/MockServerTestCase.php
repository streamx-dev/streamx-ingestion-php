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
    protected static string $pagesSchemaName;
    protected static string $dummySchemaName;

    protected StreamxClient $client;

    public static function setUpBeforeClass(): void
    {
        self::$server = new MockWebServer();
        self::$server->start();
        self::$server->setDefaultResponse(StreamxResponse::success(-1, 'any'));
        self::$pagesSchemaName = 'dev.streamx.data.model.PageIngestionMessage';
        self::$dummySchemaName = 'dev.streamx.data.model.DummyIngestionMessage';
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
        return $this->client->newPublisher("pages", self::$pagesSchemaName);
    }

    protected function createPublisherWithIrrelevantSchema(string $channel) : Publisher
    {
        return $this->client->newPublisher($channel, self::$dummySchemaName);
    }

    protected function defaultPublishMessageJson($key, $payload): string
    {
        return '{"key":"'.$key.'","action":"publish","eventTime":null,"properties":{},"payload":{"dev.streamx.data.model.Page":'.$payload.'}}';
    }

    protected function defaultUnpublishMessageJson($key): string
    {
        return '{"key":"'.$key.'","action":"unpublish","eventTime":null,"properties":{},"payload":null}';
    }

    protected function assertIngestionPostRequest(
        RequestInfo $request,
        string $uri,
        string $expectedBody,
        array $headers = null
    ): void {
        $this->assertEquals('POST', $request->getRequestMethod());
        $this->assertEquals($uri, $request->getRequestUri());
        $this->assertEquals($expectedBody, $request->getInput());
        $this->assertEquals('application/json; charset=UTF-8', $request->getHeaders()['Content-Type']);
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