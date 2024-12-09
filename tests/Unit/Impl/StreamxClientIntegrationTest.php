<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Unit\Impl;

use PHPUnit\Framework\TestCase;
use Streamx\Clients\Ingestion\Impl\DefaultJsonProvider;
use Streamx\Clients\Ingestion\Impl\RestPublisher;
use Streamx\Clients\Ingestion\StreamXClient;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Publisher\Message;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use Streamx\Clients\Ingestion\Tests\Testing\Model\Content;
use Streamx\Clients\Ingestion\Tests\Testing\Model\Data;

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

    private const DATA_CHANNEL = "data";
    private const DATA_SCHEMA_NAME = 'dev.streamx.blueprints.data.DataIngestionMessage';

    private const DATA_OBJECT_KEY = "data-object-key";
    private const DATA_ARRAY_KEY = "data-array-key";
    private const MESSAGE_OBJECT_KEY = "message-object-key";
    private const MESSAGE_OBJECT_WITH_DATA_ARRAY_KEY = "message-object-with-data-array-key";
    private const MULTIMESSAGE_DATA_OBJECT_KEY = "multimessage-data-object-key";

    private const CONTENT = "test content from php client";
    private const TIMEOUT_SECONDS = 3;

    private static StreamXClient $client;
    private static Publisher $publisher;
    private static Data $data;
    private static array $dataArray;

    public static function setUpBeforeClass(): void {
        self::$client = StreamxClientBuilders::create(self::INGESTION_BASE_URL)->build();
        self::$publisher = self::$client->newPublisher(self::DATA_CHANNEL, self::DATA_SCHEMA_NAME);
        self::$data = new Data(new Content(self::CONTENT));
        self::$dataArray = ['content' => ['bytes' => self::CONTENT]];
    }

    //** @test */
    public function shouldPublishAndUnpublishDataObject() {
        $this->shouldPublishAndUnpublishDataPayload(
            self::DATA_OBJECT_KEY,
            self::$data
        );
    }

    //** @test */
    public function shouldPublishAndUnpublishDataArray() {
        $this->shouldPublishAndUnpublishDataPayload(
            self::DATA_ARRAY_KEY,
            self::$dataArray
        );
    }

    private function shouldPublishAndUnpublishDataPayload($key, $dataPayload) {
        self::$publisher->publish($key, $dataPayload);
        $this->assertDataIsPublished($key);

        self::$publisher->unpublish($key);
        $this->assertDataIsUnpublished($key);
    }

    //** @test */
    public function shouldPublishAndUnpublishMessageWithDataObject() {
        $this->shouldPublishAndUnpublishDataMessage(
            self::MESSAGE_OBJECT_KEY,
            self::$data
        );
    }

    //** @test */
    public function shouldPublishAndUnpublishMessageWithDataArray() {
        $this->shouldPublishAndUnpublishDataMessage(
            self::MESSAGE_OBJECT_WITH_DATA_ARRAY_KEY,
            self::$dataArray
        );
    }

    private function shouldPublishAndUnpublishDataMessage(string $key, $dataPayload) {
        $message = (Message::newPublishMessage($key, $dataPayload))
            ->withEventTime((int) (microtime(true) * 1000))
            ->withProperties(['prop-1' => 'value-1', 'prop-2' => 'value-2'])
            ->build();
        self::$publisher->send($message);
        $this->assertDataIsPublished($key);

        $message = (Message::newUnpublishMessage($key))->build();
        self::$publisher->send($message);
        $this->assertDataIsUnpublished($key);
    }

    //** @test */
    public function shouldPublishAndUnpublishMultiMessageRequest() {
        $keys = [];
        for ($i = 0; $i < 10; $i++) {
            $keys[] = self::MULTIMESSAGE_DATA_OBJECT_KEY . "_$i";
        }

        $this->verifyMultiMessagePublish($keys);
        $this->verifyMultiMessageUnpublish($keys);
    }

    private function verifyMultiMessagePublish(array $keys) {
        $jsonFormatter = new DefaultJsonProvider();
        $payloadTypeName = RestPublisher::convertToPayloadTypeName(self::DATA_SCHEMA_NAME);

        // given
        $singlePublishJsons = [];
        foreach ($keys as $key) {
            $publishMessage = Message::newPublishMessage($key, self::$dataArray)->build();
            $singlePublishJsons[] = $jsonFormatter->getJson($publishMessage, $payloadTypeName);
        }
        $multiPublishJson = implode("\n", $singlePublishJsons);

        // when
        self::$publisher->sendMulti($multiPublishJson);

        // then
        foreach ($keys as $key) {
            $this->assertDataIsPublished($key);
        }
    }

    private function verifyMultiMessageUnpublish(array $keys) {
        // given
        $singleUnpublishJsons = [];
        foreach ($keys as $key) {
            $unpublishMessage = Message::newUnpublishMessage($key)->build();
            $singleUnpublishJsons[] = json_encode($unpublishMessage, JSON_PRETTY_PRINT);
        }
        $multiUnpublishJson = implode("\n", $singleUnpublishJsons);

        // when
        self::$publisher->sendMulti($multiUnpublishJson);

        // then
        foreach ($keys as $key) {
            $this->assertDataIsUnpublished($key);
        }
    }

    private function assertDataIsPublished(string $key) {
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
        
        $this->fail("$url: not found");
    }

    private function assertDataIsUnpublished(string $key) {
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
        
        $this->fail("$url: exists");
    }
}

