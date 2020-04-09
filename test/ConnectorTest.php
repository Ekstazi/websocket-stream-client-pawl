<?php

namespace ekstazi\websocket\client\pawl\test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Success;
use ekstazi\websocket\client\ConnectionFactory;
use ekstazi\websocket\client\pawl\Connection;
use ekstazi\websocket\client\pawl\Connector;
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

        self::assertInstanceOf(Connection::class, $connection);
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
