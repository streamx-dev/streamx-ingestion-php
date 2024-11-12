<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Unit\Impl;

use PHPUnit\Framework\TestCase;
use Streamx\Clients\Ingestion\StreamXClient;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;
use Streamx\Clients\Ingestion\Publisher\Message;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use Streamx\Clients\Ingestion\Tests\Testing\Model\Content;
use Streamx\Clients\Ingestion\Tests\Testing\Model\Page;

/**
 * Integration test to be executed manually on demand.
 * To do so, remove first slash from each @test header above the test methods to be executed.
 * Requirements: running StreamX instance, with:
 *  - ingestion service available at http://localhost:8080
 *  - web delivery service available at http://localhost:8081
 *  - pages schema same as in tests/resources/integration-pages-schema.avsc
 * If you need to configure those requirements to match to your StreamX instance - please don't commit such changes
 */
class StreamxClientIntegrationTest extends TestCase {

    private static /*StreamXClient*/ $client;
    private static /*Publisher*/ $publisher;
    private static /*string*/ $pagesSchemaJson;

    private const INGESTION_BASE_URL = "http://localhost:8080";
    private const DELIVERY_BASE_URL = "http://localhost:8081";
    private const PAGE_KEY = "data-key-from-php";
    private const MESSAGE_KEY = "message-key-from-php";
    private const CONTENT = "some bytes from php client";
    private const TIMEOUT_SECONDS = 3;

    public static function setUpBeforeClass(): void {
        self::$client = StreamxClientBuilders::create(self::INGESTION_BASE_URL)->build();
        self::$pagesSchemaJson = file_get_contents('tests/resources/integration-pages-schema.avsc');
        self::$publisher = self::$client->newPublisher("pages", self::$pagesSchemaJson);
    }

    //** @test */
    public function shouldPublishPageToStreamX() {
        $page = new Page(new Content(self::CONTENT));
        self::$publisher->publish(self::PAGE_KEY, $page);
        $this->assertPageIsPublished(self::PAGE_KEY);
    }

    //** @test */
    public function shouldUnpublishPageFromStreamX() {
        self::$publisher->unpublish(self::PAGE_KEY);
        $this->assertPageIsUnpublished(self::PAGE_KEY);
    }

    //** @test */
    public function shouldPublishPageMessageToStreamX() {
        $page = new Page(new Content(self::CONTENT));
        $message = (Message::newPublishMessage(self::MESSAGE_KEY, $page))->build();
        self::$publisher->send($message);
        $this->assertPageIsPublished(self::MESSAGE_KEY);
    }

    //** @test */
    public function shouldUnpublishPageMessageFromStreamX() {
        $message = (Message::newUnpublishMessage(self::MESSAGE_KEY))->build();
        self::$publisher->send($message);
        $this->assertPageIsUnpublished(self::MESSAGE_KEY);
    }

    private function assertPageIsPublished(string $key) {
        $url = self::DELIVERY_BASE_URL . '/' . $key;
    
        $startTime = time();
        while (time() - $startTime < self::TIMEOUT_SECONDS) { // wait for at most 1 second
            $response = @file_get_contents($url);
            if ($response !== false) {
                $this->assertEquals($response, self::CONTENT);
                return;
            }
            usleep(100000); // sleep for 100 milliseconds
        }
        
        $this->fail('404');
    }

    private function assertPageIsUnpublished(string $key) {
        $url = self::DELIVERY_BASE_URL . '/' . $key;
    
        $startTime = time();
        while (time() - $startTime < self::TIMEOUT_SECONDS) { // wait for at most 1 second
            $response = @file_get_contents($url);
            if (empty($response)) {
                $this->assertTrue(true);
                return;
            }
            usleep(100000); // sleep for 100 milliseconds
        }
        
        $this->fail('202');
    }
}

