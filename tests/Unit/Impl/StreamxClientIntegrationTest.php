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

    private const INGESTION_BASE_URL = "http://localhost:8080";
    private const DELIVERY_BASE_URL = "http://localhost:8081";
    private const PAGES_SCHEMA_NAME = 'dev.streamx.blueprints.data.PageIngestionMessage';

    private const PAGE_OBJECT_KEY = "page-object-key";
    private const PAGE_ARRAY_KEY = "page-array-key";
    private const MESSAGE_OBJECT_KEY = "message-object-key";
    private const MESSAGE_OBJECT_WITH_PAGE_ARRAY_KEY = "message-object-with-page-array-key";

    private const CONTENT = "test content from php client";
    private const TIMEOUT_SECONDS = 3;

    private static StreamXClient $client;
    private static Publisher $publisher;
    private static Page $page;
    private static array $pageArray;

    public static function setUpBeforeClass(): void {
        self::$client = StreamxClientBuilders::create(self::INGESTION_BASE_URL)->build();
        self::$publisher = self::$client->newPublisher("pages", self::PAGES_SCHEMA_NAME);
        self::$page = new Page(new Content(self::CONTENT));
        self::$pageArray = ['content' => ['bytes' => self::CONTENT]];
    }

    //** @test */
    public function shouldPublishAndUnpublishPageObject() {
        $this->shouldPublishAndUnpublishPagePayload(
            self::PAGE_OBJECT_KEY,
            self::$page
        );
    }

    //** @test */
    public function shouldPublishAndUnpublishPageArray() {
        $this->shouldPublishAndUnpublishPagePayload(
            self::PAGE_ARRAY_KEY,
            self::$pageArray
        );
    }

    private function shouldPublishAndUnpublishPagePayload($key, $pagePayload) {
        self::$publisher->publish($key, $pagePayload);
        $this->assertPageIsPublished($key);

        self::$publisher->unpublish($key);
        $this->assertPageIsUnpublished($key);
    }

    //** @test */
    public function shouldPublishAndUnpublishMessageWithPageObject() {
        $this->shouldPublishAndUnpublishPageMessage(
            self::MESSAGE_OBJECT_KEY,
            self::$page
        );
    }

    //** @test */
    public function shouldPublishAndUnpublishMessageWithPageArray() {
        $this->shouldPublishAndUnpublishPageMessage(
            self::MESSAGE_OBJECT_WITH_PAGE_ARRAY_KEY,
            self::$pageArray
        );
    }

    private function shouldPublishAndUnpublishPageMessage(string $key, $pagePayload) {
        $message = (Message::newPublishMessage($key, $pagePayload))
            ->withEventTime((int) (microtime(true) * 1000))
            ->withProperties(['prop-1' => 'value-1', 'prop-2' => 'value-2'])
            ->build();
        self::$publisher->send($message);
        $this->assertPageIsPublished($key);

        $message = (Message::newUnpublishMessage($key))->build();
        self::$publisher->send($message);
        $this->assertPageIsUnpublished($key);
    }

    private function assertPageIsPublished(string $key) {
        $url = self::DELIVERY_BASE_URL . '/' . $key;
    
        $startTime = time();
        while (time() - $startTime < self::TIMEOUT_SECONDS) {
            $response = @file_get_contents($url);
            if ($response !== false) {
                $this->assertEquals($response, self::CONTENT);
                return;
            }
            usleep(100000); // sleep for 100 milliseconds
        }
        
        $this->fail("$url: page not found");
    }

    private function assertPageIsUnpublished(string $key) {
        $url = self::DELIVERY_BASE_URL . '/' . $key;
    
        $startTime = time();
        while (time() - $startTime < self::TIMEOUT_SECONDS) {
            $response = @file_get_contents($url);
            if (empty($response)) {
                $this->assertTrue(true);
                return;
            }
            usleep(100000); // sleep for 100 milliseconds
        }
        
        $this->fail("$url: page exists");
    }
}

