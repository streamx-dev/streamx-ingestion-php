<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Unit\Impl;

use donatj\MockWebServer\Response;
use PHPUnit\Framework\Attributes\Test;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Tests\Testing\Impl\CustomTestHttpRequester;
use Streamx\Clients\Ingestion\Tests\Testing\Impl\CustomTestJsonProvider;
use Streamx\Clients\Ingestion\Tests\Testing\MockServerTestCase;
use Symfony\Component\HttpClient\Psr18Client;

class RestStreamxClientBuilderTest extends MockServerTestCase
{

    #[Test]
    public function shouldSetCustomPublicationsEndpointUri()
    {
        // Given
        $data = ['message' => 'test'];

        self::$server->setResponseOfPath('/custom-publications/v2/channel/key',
            new Response('{"eventTime":"836383"}', [], 202));

        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())
            ->setPublicationsEndpointUri('/custom-publications/v2')
            ->build();

        // When
        $result = $this->client->newPublisher("channel")->publish("key", $data);

        // Then
        $this->assertPut(self::$server->getLastRequest(),
            '/custom-publications/v2/channel/key',
            '{"message":"test"}');

        $this->assertEquals(836383, $result->getEventTime());
    }

    #[Test]
    public function shouldSetCustomHttpRequester()
    {
        // Given
        $data = ['message' => 'custom requester'];

        self::$server->setResponseOfPath('/custom-requester-publications/v1/channel/key',
            new Response('{"eventTime":"937493"}', [], 202));

        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())
            ->setHttpRequester(new CustomTestHttpRequester('/custom-requester-publications/v1'))
            ->build();

        // When
        $result = $this->client->newPublisher("channel")->publish("key", $data);

        // Then
        $this->assertPut(self::$server->getLastRequest(),
            '/custom-requester-publications/v1/channel/key',
            '{"message":"custom requester"}');

        $this->assertEquals(937493, $result->getEventTime());
    }

    #[Test]
    public function shouldSetCustomHttpClient()
    {
        // Given
        $data = ['message' => 'custom http client'];

        self::$server->setResponseOfPath('/publications/v1/channel/key',
            new Response('{"eventTime":"937493"}', [], 202));

        $symphonyClient = (new Psr18Client())->withOptions(['headers' => ['X-StreamX' => 'Custom http client']]);

        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())
            ->setHttpClient($symphonyClient)
            ->build();

        // When
        $result = $this->client->newPublisher("channel")->publish("key", $data);

        // Then
        $this->assertPut(self::$server->getLastRequest(),
            '/publications/v1/channel/key',
            '{"message":"custom http client"}',
            ['X-StreamX' => 'Custom http client']);

        $this->assertEquals(937493, $result->getEventTime());
    }

    #[Test]
    public function shouldSetCustomJsonProvider()
    {
        // Given
        $data = ['property' => 'original'];

        self::$server->setResponseOfPath('/publications/v1/channel/key',
            new Response('{"eventTime":"625436"}', [], 202));

        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())
            ->setJsonProvider(new CustomTestJsonProvider('Added by custom Json Provider'))
            ->build();

        // When
        $result = $this->client->newPublisher("channel")->publish("key", $data);

        // Then
        $this->assertPut(self::$server->getLastRequest(),
            '/publications/v1/channel/key',
            '{"property":"original","customProperty":"Added by custom Json Provider"}');

        $this->assertEquals(625436, $result->getEventTime());
    }
}