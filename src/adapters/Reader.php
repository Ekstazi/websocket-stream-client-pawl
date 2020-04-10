<?php

namespace ekstazi\websocket\client\pawl\adapters;

use Amp\ByteStream\ClosedException;
use Amp\ByteStream\IteratorStream;
use Amp\ByteStream\StreamException;
use Amp\Emitter;
use Amp\Promise;
use ekstazi\websocket\common\Reader as ReaderInterface;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Frame;

final class Reader implements ReaderInterface
{
    /**
     * @var WebSocket
     */
    private $webSocket;
    /**
     * @var Emitter
     */
    private $emitter;
    /**
     * @var IteratorStream
     */
    private $iteratorStream;

    public function __construct(WebSocket $webSocket)
    {
        $this->webSocket = $webSocket;
        $this->emitter = new Emitter();
        $this->iteratorStream = new IteratorStream($this->emitter->iterate());


        $this->attachHandlers();
    }

    private function attachHandlers()
    {
        $this->webSocket->on("message", function (string $chunk) {
            $this->webSocket->pause();
            $this->emitter->emit($chunk)->onResolve(function () {
                $this->webSocket->resume();
            });
        });

        $this->webSocket->on("error", function (\Throwable $error) {
            if ($this->emitter) {
                $emitter = $this->emitter;
                $this->emitter = null;
                $emitter->fail(new StreamException($error->getMessage(), $error->getCode(), $error));
            }
        });

        $this->webSocket->on("close", function (int $code, string $reason = '') {
            if (!$this->emitter) {
                return;
            }

            $emitter = $this->emitter;
            $this->emitter = null;
            if ($code !== Frame::CLOSE_NORMAL) {
                $emitter->fail(new ClosedException($reason, $code));
            } else {
                $emitter->complete();
            }
        });
    }

    /** @inheritdoc */
    public function read(): Promise
    {
        return $this->iteratorStream->read();
    }
}
