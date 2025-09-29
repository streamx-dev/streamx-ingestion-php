<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Unit\Impl;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Publisher\MessageStatus;
use Streamx\Clients\Ingestion\Publisher\SuccessResult;
use Streamx\Clients\Ingestion\Tests\Testing\Impl\CustomTestHttpRequester;
use Streamx\Clients\Ingestion\Tests\Testing\Impl\CustomTestJsonProvider;
use Streamx\Clients\Ingestion\Tests\Testing\MockServerTestCase;
use Streamx\Clients\Ingestion\Tests\Testing\StreamxResponse;

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
        $responseJson = json_encode($messageStatus);

        $responseBodyMock = $this->createMock(StreamInterface::class);
        $responseBodyMock->method('eof')->willReturn(
            false,
            false,
            true
        );
        $responseBodyMock->method('read')->willReturn(
            substr($responseJson, 0, 15),
            substr($responseJson, 15)
        );
    
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(202);
        $responseMock->method('getBody')->willReturn($responseBodyMock);

        $clientMock = $this->createMock(Client::class);
        $clientMock->method('request')->willReturnCallback(function($method, $uri, $options) use ($url, $responseMock)
        {
            $options = [
                'http' => [
                    'method'  => $method,
                    'header'  => "Content-Type: application/json; charset=UTF-8\r\nX-StreamX: Custom http client",
                    'content' => $options[RequestOptions::BODY],
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