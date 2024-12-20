<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Unit\Impl;

use PHPUnit\Framework\TestCase;
use Streamx\Clients\Ingestion\Impl\MessageStatus;
use Streamx\Clients\Ingestion\StreamXClient;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
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
 * If you need to configure those requirements to match to your StreamX instance - please don't commit such changes
 */
class StreamxClientIntegrationTest extends TestCase {

    private const INGESTION_BASE_URL = "http://localhost:8080";
    private const DELIVERY_BASE_URL = "http://localhost:8081";

    private const PAGES_CHANNEL = "pages";
    private const PAGE_SCHEMA_NAME = 'dev.streamx.blueprints.data.PageIngestionMessage';

    private const PAGE_OBJECT_KEY = "page-object-key";
    private const PAGE_ARRAY_KEY = "page-array-key";
    private const MESSAGE_OBJECT_KEY = "message-object-key";
    private const MESSAGE_OBJECT_WITH_PAGE_ARRAY_KEY = "message-object-with-page-array-key";
    private const MULTIMESSAGE_PAGE_OBJECT_KEY = "multimessage-page-object-key";

    private const CONTENT = "test content from php client";
    private const TIMEOUT_SECONDS = 3;

    private static StreamXClient $client;
    private static Publisher $publisher;
    private static Page $page;
    private static array $pageArray;

    public static function setUpBeforeClass(): void {
        self::$client = StreamxClientBuilders::create(self::INGESTION_BASE_URL)->build();
        self::$publisher = self::$client->newPublisher(self::PAGES_CHANNEL, self::PAGE_SCHEMA_NAME);
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

    //** @test */
    public function shouldPublishAndUnpublishMultiMessageRequest() {
        $keys = [];
        for ($i = 0; $i < 10; $i++) {
            $keys[] = self::MULTIMESSAGE_PAGE_OBJECT_KEY . "_$i";
        }

        $this->verifyMultiMessagePublish($keys);
        $this->verifyMultiMessageUnpublish($keys);
    }

    private function verifyMultiMessagePublish(array $keys) {
        // given
        $messages = [];
        foreach ($keys as $key) {
            $messages[] = Message::newPublishMessage($key, self::$pageArray)->build();
        }

        // when
        $results = self::$publisher->sendMulti($messages);

        // then
        $this->verifyStreamxResponse($keys, $results);

        // and
        foreach ($keys as $key) {
            $this->assertPageIsPublished($key);
        }
    }

    private function verifyMultiMessageUnpublish(array $keys) {
        // given
        $messages = [];
        foreach ($keys as $key) {
            $messages[] = Message::newUnpublishMessage($key)->build();
        }

        // when
        $results = self::$publisher->sendMulti($messages);

        // then
        $this->verifyStreamxResponse($keys, $results);

        // and
        foreach ($keys as $key) {
            $this->assertPageIsUnpublished($key);
        }
    }

    private function verifyStreamxResponse(array $inputMessageKeys, array $ingestionEndpointResults): void {
        $this->assertSameSize($inputMessageKeys, $ingestionEndpointResults);
        for ($i = 0; $i < count($ingestionEndpointResults); $i++) {
            $result = $ingestionEndpointResults[$i];
            $this->assertInstanceOf(MessageStatus::class, $result);
            $this->assertNotNull($result->getSuccess());
            $this->assertEquals($inputMessageKeys[$i], $result->getSuccess()->getKey());
            $this->assertIsInt($result->getSuccess()->getEventTime());
        }
    }

    //** @test */
    public function shouldRetrieveChannelSchema() {
        // when
        $schemaJson = self::$publisher->getSchema();

        // then
        $schemaArray = json_decode($schemaJson, true);
        $this->assertEquals('record', $schemaArray['type']);
        $this->assertEquals('PageIngestionMessage', $schemaArray['name']);
        $this->assertEquals('dev.streamx.blueprints.data', $schemaArray['namespace']);

        $fieldNames = [];
        foreach ($schemaArray['fields'] as $field) {
            $fieldNames[] = $field['name'];
        }
        $this->assertEquals(['key', 'action', 'eventTime', 'properties', 'payload'], $fieldNames);
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

