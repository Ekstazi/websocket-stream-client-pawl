<?php

namespace ekstazi\websocket\client\pawl;

use Amp\Promise;
use Amp\ReactAdapter\ReactAdapter;
use ekstazi\websocket\client\ConnectionFactory;
use ekstazi\websocket\client\pawl\adapters\Reader;
use ekstazi\websocket\client\pawl\adapters\Writer;

use Psr\Http\Message\RequestInterface;
use Ratchet\Client\Connector as PawlConnector;
use Ratchet\Client\WebSocket;
use function Amp\call;

final class Connector implements ConnectionFactory
{
    /**
     * @var PawlConnector
     */
    private $connector;

    public function __construct(PawlConnector $connector = null)
    {
        $this->connector = $connector ?? new PawlConnector(ReactAdapter::get());
    }

    public function connect(RequestInterface $request, string $mode = Connection::MODE_BINARY): Promise
    {
        return call(function () use ($request, $mode) {
            /** @var WebSocket $connection */
            $connection = yield $this->connector->__invoke($request->getUri(), $request->getHeader('Sec-Websocket-Protocol'), $request->getHeaders());
            return new Connection(
                new Reader($connection),
                new Writer($connection, $mode)
            );
        });
    }
}
