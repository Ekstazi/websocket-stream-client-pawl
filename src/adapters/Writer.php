<?php

namespace ekstazi\websocket\stream\pawl\adapters;

use Amp\ByteStream\OutputStream;
use Amp\Deferred;
use Amp\Failure;
use Amp\Promise;
use Amp\Success;
use ekstazi\websocket\stream\ConnectionFactory;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Frame;
use function Amp\call;

class Writer implements OutputStream
{
    /**
     * @var WebSocket
     */
    private $webSocket;
    /**
     * @var string
     */
    private $frameType;

    /**
     * @var \Throwable|null
     */
    private $error;

    /**
     * @var Deferred
     */
    private $backpressure;

    public function __construct(WebSocket $webSocket, string $mode = ConnectionFactory::MODE_BINARY)
    {
        $this->webSocket = $webSocket;

        $this->frameType = $mode == ConnectionFactory::MODE_BINARY
            ? Frame::OP_BINARY
            : Frame::OP_TEXT;

        $this->attachHandlers();
    }

    private function attachHandlers()
    {
        $this->webSocket->on("error", function (\Throwable $error) use (&$deferred) {
            $this->error = $error;
        });

        $this->webSocket->on("drain", function () {
            if ($this->backpressure) {
                $backpressure = $this->backpressure;
                $this->backpressure = null;
                $backpressure->resolve();
            }
        });
    }

    /** @inheritdoc */
    public function write(string $data): Promise
    {
        if ($this->error) {
            return new Failure($this->error);
        }

        $shouldStop = $this->webSocket->send(new Frame($data, true, $this->frameType));

        if ($shouldStop) {
            return new Success();
        }

        // There might be multiple write calls without the backpressure being resolved
        if (!$this->backpressure) {
            $this->backpressure = new Deferred;
        }

        return $this->backpressure->promise();
    }

    /** @inheritdoc */
    public function end(string $finalData = ""): Promise
    {
        return call(function () use ($finalData) {
            if ($this->error) {
                return new Failure($this->error);
            }

            if ($finalData) {
                yield $this->write($finalData);
            }

            $deferred = new Deferred;

            $this->webSocket->on("close", function (int $code, string $reason) use ($deferred) {
                if ($code !== Frame::CLOSE_NORMAL) {
                    $this->error = new \Exception($reason, $code);
                }
                if ($this->error) {
                    $deferred->fail($this->error);
                } else {
                    $deferred->resolve();
                }
            });

            $this->webSocket->close();
            return $deferred->promise();
        });
    }
}
