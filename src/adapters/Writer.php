<?php

namespace ekstazi\websocket\client\pawl\adapters;

use Amp\ByteStream\ClosedException;
use Amp\ByteStream\StreamException;
use Amp\Deferred;
use Amp\Failure;
use Amp\Promise;
use Amp\Success;
use ekstazi\websocket\common\internal\SetModeTrait;
use ekstazi\websocket\common\Writer as WriterInterface;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Frame;
use function Amp\call;

final class Writer implements WriterInterface
{
    use SetModeTrait;
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

    public function __construct(WebSocket $webSocket, string $defaultMode = self::MODE_BINARY)
    {
        $this->webSocket = $webSocket;
        $this->setDefaultMode($defaultMode);
        $this->attachHandlers();
    }

    private function getFrameOpCodeByMode(string $mode): int
    {
        switch ($mode) {
            case self::MODE_BINARY:
                return Frame::OP_BINARY;

            case self::MODE_TEXT:
                return Frame::OP_TEXT;
        }
    }

    private function attachHandlers()
    {
        $this->webSocket->on("error", function (\Throwable $error) use (&$deferred) {
            $this->error = new StreamException($error->getMessage(), $error->getCode(), $error);
        });

        $this->webSocket->on("close", function ($code, $reason) use (&$deferred) {
            $this->error = new ClosedException("The stream was closed. " . $reason, $code);
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
    public function write(string $data, string $mode = null): Promise
    {
        $mode = $mode ?? $this->defaultMode;
        $this->guardValidMode($mode);

        if ($this->error) {
            return new Failure($this->error);
        }

        $shouldStop = $this->webSocket->send(new Frame($data, true, $this->getFrameOpCodeByMode($mode)));

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
    public function end(string $finalData = "", string $mode = null): Promise
    {
        return call(function () use ($finalData, $mode) {
            if ($this->error) {
                return new Failure($this->error);
            }

            if ($finalData) {
                yield $this->write($finalData, $mode);
            }

            $deferred = new Deferred;

            $this->webSocket->on("close", function (int $code, string $reason) use ($deferred) {
                if ($code !== Frame::CLOSE_NORMAL) {
                    $this->error = new ClosedException("Underlying connection closed" . $reason, $code);
                }
                if ($code !== Frame::CLOSE_NORMAL) {
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
