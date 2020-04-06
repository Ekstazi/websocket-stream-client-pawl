<?php

namespace ekstazi\websocket\stream\pawl\adapters;

use Amp\ByteStream\InputStream;
use Amp\ByteStream\IteratorStream;
use Amp\Emitter;
use Amp\Promise;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Frame;

class Reader implements InputStream
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
            // @todo pause/resume
            $this->emitter->emit($chunk);
        });

        $this->webSocket->on("error", function (\Throwable $error) {
            if ($this->emitter) {
                $emitter = $this->emitter;
                $this->emitter = null;
                $emitter->fail($error);
            }
        });

        $this->webSocket->on("close", function (int $code, string $reason = '') {
            if (!$this->emitter) {
                return;
            }

            $emitter = $this->emitter;
            $this->emitter = null;
            if ($code !== Frame::CLOSE_NORMAL) {
                $emitter->fail(new \Exception($reason, $code));
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
