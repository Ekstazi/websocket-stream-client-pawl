<?php

namespace ekstazi\websocket\stream;

use Amp\Loop;
use Amp\Promise;
use ekstazi\websocket\stream\pawl\Connector;
use Psr\Http\Message\RequestInterface;

const LOOP_CONNECTOR_IDENTIFIER = ConnectionFactory::class;

/**
 * Set or access the global websocket Connector instance.
 *
 * @param Connector|null $connector
 *
 * @return Connector
 */
function connector(?Connector $connector = null): Connector
{
    if ($connector === null) {
        $connector = Loop::getState(LOOP_CONNECTOR_IDENTIFIER);
        if ($connector) {
            return $connector;
        }

        $connector = new Connector();
    }

    Loop::setState(LOOP_CONNECTOR_IDENTIFIER, $connector);
    return $connector;
}

/**
 * @param RequestInterface $request
 * @param string $mode
 * @return Promise<Stream>
 * @throws
 */
function connect(RequestInterface $request, string $mode = ConnectionFactory::MODE_BINARY): Promise
{
    return connector()->connect($request, $mode);
}
