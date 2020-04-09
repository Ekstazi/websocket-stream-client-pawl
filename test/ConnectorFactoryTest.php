<?php

namespace ekstazi\websocket\client\pawl\test;

use Amp\ReactAdapter\ReactAdapter;
use ekstazi\websocket\client\ConnectionFactory;
use ekstazi\websocket\client\pawl\ConnectorFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Ratchet\Client\Connector as PawlConnector;

class ConnectorFactoryTest extends TestCase
{
    public function testInvokeInstance()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects(self::once())
            ->method('has')
            ->willReturn(true);

        $container
            ->expects(self::once())
            ->method('get')
            ->willReturn(new PawlConnector(ReactAdapter::get()));

        $factory = new ConnectorFactory();
        $connector = $factory->__invoke($container);
        self::assertInstanceOf(ConnectionFactory::class, $connector);
    }

    public function testInvokeDefault()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects(self::once())
            ->method('has')
            ->willReturn(false);

        $container
            ->expects(self::never())
            ->method('get');

        $factory = new ConnectorFactory();
        $connector = $factory->__invoke($container);
        self::assertInstanceOf(ConnectionFactory::class, $connector);
    }
}
