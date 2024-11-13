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

    private static StreamXClient $client;
    private static Publisher $publisher;

    private const INGESTION_BASE_URL = "http://localhost:8080";
    private const DELIVERY_BASE_URL = "http://localhost:8081";
    private const PAGES_SCHEMA_NAME = 'dev.streamx.blueprints.data.PageIngestionMessage';

    private const PAGE_MAP_KEY = "page-map-key";
    private const PAGE_OBJECT_KEY = "page-object-key";
    private const MESSAGE_OBJECT_KEY = "message-object-key";

    private const CONTENT = "test content from php client";
    private const TIMEOUT_SECONDS = 3;

    public static function setUpBeforeClass(): void {
        self::$client = StreamxClientBuilders::create(self::INGESTION_BASE_URL)->build();
        self::$publisher = self::$client->newPublisher("pages", self::PAGES_SCHEMA_NAME);
    }

    //** @test */
    public function shouldPublishPageObject() {
        $key = self::PAGE_OBJECT_KEY;
        $page = new Page(new Content(self::CONTENT));
        self::$publisher->publish($key, $page);
        $this->assertPageIsPublished($key);
    }

    //** @test */
    public function shouldUnpublishPageObject() {
        $key = self::PAGE_OBJECT_KEY;
        self::$publisher->unpublish($key);
        $this->assertPageIsUnpublished($key);
    }

    //** @test */
    public function shouldPublishPageArray() {
        $key = self::PAGE_MAP_KEY;
        $page = ['content' => ['bytes' => self::CONTENT]];
        self::$publisher->publish($key, $page);
        $this->assertPageIsPublished($key);
    }

    //** @test */
    public function shouldUnpublishPageArray() {
        $key = self::PAGE_MAP_KEY;
        self::$publisher->unpublish($key);
        $this->assertPageIsUnpublished($key);
    }

    /** @test */
    public function shouldPublishMessageObject() {
        $key = self::MESSAGE_OBJECT_KEY;
        $page = new Page(new Content(self::CONTENT));
        $message = (Message::newPublishMessage($key, $page))->build();
        self::$publisher->send($message);
        $this->assertPageIsPublished($key);
    }

    /** @test */
    public function shouldUnpublishMessageObjectFromStreamX() {
        $key = self::MESSAGE_OBJECT_KEY;
        $message = (Message::newUnpublishMessage($key))->build();
        self::$publisher->send($message);
        $this->assertPageIsUnpublished($key);
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

