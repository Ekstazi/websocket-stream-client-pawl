<?php

namespace ekstazi\websocket\stream\pawl\test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Success;
use ekstazi\websocket\stream\ConnectionFactory;
use ekstazi\websocket\stream\pawl\Connector;
use ekstazi\websocket\stream\Stream;
use Psr\Http\Message\RequestInterface;

use Psr\Http\Message\UriInterface;
use Ratchet\Client\Connector as PawlConnector;
use Ratchet\Client\WebSocket;

class ConnectorTest extends AsyncTestCase
{
    private function stubRequest(): RequestInterface
    {
        $request = $this->createStub(RequestInterface::class);
        $uri = $this->createStub(UriInterface::class);
        $uri->method('getScheme')
            ->willReturn('ws');
        $request->method('getUri')
            ->willReturn($uri);

        $request->method('getHeaders')
            ->willReturn([
                'test-header' => ['test'],
            ]);

        $request->method('getHeader')
            ->willReturn([]);
        return $request;
    }

    /**
     * Test that connector is instance of ConnectionFactory.
     */
    public function testInstanceOf()
    {
        $connector = new Connector();
        self::assertInstanceOf(ConnectionFactory::class, $connector);
    }

    /**
     * Test that request parameters used to create connection.
     * @return \Generator
     * @throws
     */
    public function testConnect()
    {
        $request = $this->stubRequest();
        $client = $this->createMock(PawlConnector::class);
        $client->expects(self::once())
            ->method('__invoke')
            ->with($request->getUri(), $request->getHeader('Sec-websocket-protocol'), $request->getHeaders())
            ->willReturn(new Success($this->stubWebsocket()));

        $connector = new Connector($client);
        $connection = yield $connector->connect($request);

        self::assertInstanceOf(Stream::class, $connection);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|WebSocket
     * @throws \ReflectionException
     * @todo move to websocket mock builder
     */
    private function stubWebsocket()
    {
        return $this->createMock(WebSocket::class);
    }
}
