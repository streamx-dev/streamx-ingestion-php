<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Unit\Impl;

use donatj\MockWebServer\Response;
use PHPUnit\Framework\Attributes\Test;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Tests\Testing\Impl\CustomTestHttpRequester;
use Streamx\Clients\Ingestion\Tests\Testing\Impl\CustomTestJsonProvider;
use Streamx\Clients\Ingestion\Tests\Testing\MockServerTestCase;
use Streamx\Clients\Ingestion\Tests\Testing\StreamxResponse;
use Symfony\Component\HttpClient\Psr18Client;

class RestStreamxClientBuilderTest extends MockServerTestCase
{

    #[Test]
    public function shouldSetCustomIngestionEndpointUri()
    {
        // Given
        $key = 'key';
        $data = ['message' => 'test'];

        self::$server->setResponseOfPath('/custom-ingestion/v2/channels/channel/messages',
            StreamxResponse::success(836383, $key));

        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())
            ->setIngestionEndpointUri('/custom-ingestion/v2')
            ->build();

        // When
        $result = $this->client->newPublisher("channel")->publish($key, $data);

        // Then
        $this->assertPublishPostRequest(self::$server->getLastRequest(),
            '/custom-ingestion/v2/channels/channel/messages',
            $key,
            '{"message":"test"}');

        $this->assertEquals(836383, $result->getEventTime());
        $this->assertEquals($key, $result->getKey());
    }

    #[Test]
    public function shouldSetCustomHttpRequester()
    {
        // Given
        $key = 'key';
        $data = ['message' => 'custom requester'];

        self::$server->setResponseOfPath('/custom-requester-ingestion/v1/channels/channel/messages',
            StreamxResponse::success(937493, $key));

        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())
            ->setHttpRequester(new CustomTestHttpRequester('/custom-requester-ingestion/v1'))
            ->build();

        // When
        $result = $this->client->newPublisher("channel")->publish("key", $data);

        // Then
        $this->assertPublishPostRequest(self::$server->getLastRequest(),
            '/custom-requester-ingestion/v1/channels/channel/messages',
            $key,
            '{"message":"custom requester"}');

        $this->assertEquals(937493, $result->getEventTime());
        $this->assertEquals($key, $result->getKey());
    }

    #[Test]
    public function shouldSetCustomHttpClient()
    {
        // Given
        $key = 'key';
        $data = ['message' => 'custom http client'];

        self::$server->setResponseOfPath('/ingestion/v1/channels/channel/messages',
            StreamxResponse::success(937493, $key));

        $symphonyClient = (new Psr18Client())->withOptions(['headers' => ['X-StreamX' => 'Custom http client']]);

        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())
            ->setHttpClient($symphonyClient)
            ->build();

        // When
        $result = $this->client->newPublisher("channel")->publish("key", $data);

        // Then
        $this->assertPublishPostRequest(self::$server->getLastRequest(),
            '/ingestion/v1/channels/channel/messages',
            'key',
            '{"message":"custom http client"}',
            ['X-StreamX' => 'Custom http client']);

        $this->assertEquals(937493, $result->getEventTime());
        $this->assertEquals($key, $result->getKey());
    }

    #[Test]
    public function shouldSetCustomJsonProvider()
    {
        // Given
        $key = 'key';
        $data = ['property' => 'original'];

        self::$server->setResponseOfPath('/ingestion/v1/channels/channel/messages',
            StreamxResponse::success(625436, $key));

        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())
            ->setJsonProvider(new CustomTestJsonProvider('Added by custom Json Provider'))
            ->build();

        // When
        $result = $this->client->newPublisher("channel")->publish("key", $data);

        // Then
        $this->assertPublishPostRequest(self::$server->getLastRequest(),
            '/ingestion/v1/channels/channel/messages',
            'key',
            '{"property":"original","customProperty":"Added by custom Json Provider"}');

        $this->assertEquals(625436, $result->getEventTime());
        $this->assertEquals($key, $result->getKey());
    }
}