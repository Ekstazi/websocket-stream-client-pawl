<?php

namespace ekstazi\websocket\client\pawl;

use ekstazi\websocket\client\ConnectionFactory;
use Psr\Container\ContainerInterface;
use Ratchet\Client\Connector as PawlConnector;

final class ConnectorFactory
{
    public function __invoke(ContainerInterface $container): ConnectionFactory
    {
        $client = $container->has(PawlConnector::class)
            ? $container->get(PawlConnector::class)
            : null;

        return new Connector($client);
    }
}
