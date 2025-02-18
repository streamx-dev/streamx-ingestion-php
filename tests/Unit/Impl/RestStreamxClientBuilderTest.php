<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Unit\Impl;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Impl\MessageStatus;
use Streamx\Clients\Ingestion\Tests\Testing\Impl\CustomTestHttpRequester;
use Streamx\Clients\Ingestion\Tests\Testing\Impl\CustomTestJsonProvider;
use Streamx\Clients\Ingestion\Tests\Testing\MockServerTestCase;
use Streamx\Clients\Ingestion\Tests\Testing\StreamxResponse;
use Streamx\Clients\Ingestion\Publisher\SuccessResult;

class RestStreamxClientBuilderTest extends MockServerTestCase
{

    /** @test */
    public function shouldSetCustomIngestionEndpointUri()
    {
        // Given
        $key = 'key';
        $data = ['message' => 'test'];

        self::$server->setResponseOfPath('/custom-ingestion/v2/channels/pages/messages',
            StreamxResponse::success(836383, $key));

        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())
            ->setIngestionEndpointBasePath('/custom-ingestion/v2')
            ->build();

        // When
        $result = $this->createPagesPublisher()->publish($key, $data);

        // Then
        $this->assertIngestionRequest(self::$server->getLastRequest(),
            '/custom-ingestion/v2/channels/pages/messages',
            $this->defaultPublishMessageJson($key, '{"message":"test"}'));

        $this->assertEquals(836383, $result->getEventTime());
        $this->assertEquals($key, $result->getKey());
    }

    /** @test */
    public function shouldSetCustomHttpRequester()
    {
        // Given
        $key = 'key';
        $data = ['message' => 'custom requester'];

        self::$server->setResponseOfPath('/custom-requester-ingestion/v1/channels/pages/messages',
            StreamxResponse::success(937493, $key));

        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())
            ->setHttpRequester(new CustomTestHttpRequester('/custom-requester-ingestion/v1'))
            ->build();

        // When
        $result = $this->createPagesPublisher()->publish("key", $data);

        // Then
        $this->assertIngestionRequest(self::$server->getLastRequest(),
            '/custom-requester-ingestion/v1/channels/pages/messages',
            $this->defaultPublishMessageJson($key, '{"message":"custom requester"}'));

        $this->assertEquals(937493, $result->getEventTime());
        $this->assertEquals($key, $result->getKey());
    }

    /** @test */
    public function shouldSetCustomHttpClient()
    {
        // Given
        $url = self::$server->getServerRoot() . '/ingestion/v1/channels/pages/messages';
        $key = 'key';
        $data = ['message' => 'custom http client'];
        $messageStatus = MessageStatus::ofSuccess(new SuccessResult(937493, $key));

        $responseBodyMock = $this->createMock(StreamInterface::class);
        $responseBodyMock->method('eof')->willReturn(false, true);
        $responseBodyMock->method('read')->willReturn(json_encode($messageStatus));
    
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(202);
        $responseMock->method('getBody')->willReturn($responseBodyMock);

        $clientMock = $this->createMock(ClientInterface::class);
        $clientMock->method('sendRequest')->willReturnCallback(function($req) use ($url, $responseMock) 
        {
            $options = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/json; charset=UTF-8\r\nX-StreamX: Custom http client",
                    'content' => (string) $req->getBody(),
                ],
            ];

            $context = stream_context_create($options);
            file_get_contents($url, false, $context); // perform the HTTP POST request

            return $responseMock;
        });

        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())
            ->setHttpClient($clientMock)
            ->build();

        // When
        $result = $this->createPagesPublisher()->publish($key, $data);

        // Then
        $this->assertIngestionRequest(self::$server->getLastRequest(),
            '/ingestion/v1/channels/pages/messages',
            $this->defaultPublishMessageJson($key, '{"message":"custom http client"}'),
            ['X-StreamX' => 'Custom http client']);

        $this->assertEquals(937493, $result->getEventTime());
        $this->assertEquals($key, $result->getKey());
    }

    /** @test */
    public function shouldSetCustomJsonProvider()
    {
        // Given
        $key = 'key';
        $data = ['property' => 'original'];

        self::$server->setResponseOfPath('/ingestion/v1/channels/pages/messages',
            StreamxResponse::success(625436, $key));

        $this->client = StreamxClientBuilders::create(self::$server->getServerRoot())
            ->setJsonProvider(new CustomTestJsonProvider('Added by custom Json Provider'))
            ->build();

        // When
        $result = $this->createPagesPublisher()->publish("key", $data);

        // Then
        $this->assertIngestionRequest(self::$server->getLastRequest(),
            '/ingestion/v1/channels/pages/messages',
            $this->defaultPublishMessageJson($key, '{"property":"original","customProperty":"Added by custom Json Provider"}'));

        $this->assertEquals(625436, $result->getEventTime());
        $this->assertEquals($key, $result->getKey());
    }
}