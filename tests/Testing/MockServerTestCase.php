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

    protected function assertIngestionRequest(
        RequestInfo $request,
        string $uri,
        string $expectedBody,
        array $headers = null
    ): void {
        $this->assertRequest($request, 'POST', $uri, $expectedBody, $headers);
    }

    protected function assertSchemaRequest(
        RequestInfo $request,
        string $uri,
        array $headers = null
    ): void {
        $this->assertRequest($request, 'GET', $uri, null, $headers);
    }

    private function assertRequest(
        RequestInfo $request,
        string $expectedMethod,
        string $uri,
        ?string $expectedBody,
        array $headers = null
    ): void {
        $this->assertEquals($expectedMethod, $request->getRequestMethod());
        $this->assertEquals($uri, $request->getRequestUri());
        $this->assertEquals($expectedBody, $request->getInput());
        if ($expectedMethod === 'GET') {
            $this->assertNotContains('Content-Type', array_keys($request->getHeaders()));
        } else {
            $this->assertEquals('application/json; charset=UTF-8', $request->getHeaders()['Content-Type']);
        }
        $this->assertHeaders($request, $headers);
    }

    private function assertHeaders(RequestInfo $request, ?array $expectedHeaders): void
    {
        if ($expectedHeaders) {
            $requestHeaders = $request->getHeaders();
            foreach ($expectedHeaders as $name => $value) {
                $this->assertEquals($value, $requestHeaders[$name]);
            }
        }
    }
}