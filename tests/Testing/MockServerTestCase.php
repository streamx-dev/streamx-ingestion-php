<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Testing;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\RequestInfo;
use PHPUnit\Framework\TestCase;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\StreamxClient;

class MockServerTestCase extends TestCase
{
    protected static MockWebServer $server;

    protected StreamxClient $client;

    public static function setUpBeforeClass(): void
    {
        self::$server = new MockWebServer();
        self::$server->start();
        self::$server->setDefaultResponse(StreamxResponse::success(-1));
    }

    protected function setUp(): void
    {
        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())->build();
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }

    protected function assertPut(
        RequestInfo $request,
        string $uri,
        string $body,
        array $headers = null
    ): void {
        $this->assertEquals('PUT', $request->getRequestMethod());
        $this->assertEquals($uri, $request->getRequestUri());
        $this->assertEquals('application/json; charset=UTF-8',
            $request->getHeaders()['Content-Type']);
        $this->assertEquals($body, $request->getInput());
        $this->assertHeaders($request, $headers);
    }

    protected function assertDelete(RequestInfo $request, string $uri, array $headers = null): void
    {
        $this->assertEquals('DELETE', $request->getRequestMethod());
        $this->assertEquals($uri, $request->getRequestUri());
        $this->assertArrayNotHasKey('Content-Type', $request->getHeaders());
        $this->assertEmpty($request->getInput());
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