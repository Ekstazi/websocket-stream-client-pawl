<?php

namespace ekstazi\websocket\stream\pawl;

use ekstazi\websocket\stream\ConnectionFactory;
use Psr\Container\ContainerInterface;
use Ratchet\Client\Connector as PawlConnector;

class ConnectorFactory
{
    public function __invoke(ContainerInterface $container): ConnectionFactory
    {
        $client = $container->has(PawlConnector::class)
            ? $container->get(PawlConnector::class)
            : null;

        return new Connector($client);
    }
}
