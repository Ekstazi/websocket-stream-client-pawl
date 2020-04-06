<?php

namespace ekstazi\websocket\stream\pawl\test\adapters;

use Amp\Loop;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Frame;
use Ratchet\RFC6455\Messaging\Message;

class WebsocketBuilder
{
    /**
     * @var MockObject|WebSocket
     */
    private $webSocket;

    /**
     * WebsocketBuilder constructor.
     * @param WebSocket|MockObject $webSocket
     */
    public function __construct(WebSocket $webSocket)
    {
        $this->webSocket = $webSocket;
    }

    public function build(): WebSocket
    {
        return $this->webSocket;
    }

    /**
     * Defer close event to websocket.
     * @param int $code
     * @param string $reason
     * @return $this
     */
    public function deferCloseEvent(int $code = Frame::CLOSE_NORMAL, string $reason = ''): self
    {
        $this->webSocket
            ->expects(TestCase::atLeastOnce())
            ->method('on')
            ->willReturnCallback(function (string $event, callable $callback) use ($code, $reason) {
                if ($event !== 'close') {
                    return;
                }
                Loop::defer(function () use ($callback, $code, $reason) {
                    $callback($code, $reason);
                });
                return true;
            });

        return $this;
    }

    /**
     * Defer close event to websocket.
     * @param int $code
     * @param string $reason
     * @return $this
     */
    public function deferDoubleCloseEvent(int $code = Frame::CLOSE_NORMAL, string $reason = ''): self
    {
        $this->webSocket
            ->expects(TestCase::atLeastOnce())
            ->method('on')
            ->willReturnCallback(function (string $event, callable $callback) use ($code, $reason) {
                if ($event !== 'close') {
                    return;
                }
                Loop::defer(function () use ($callback, $code, $reason) {
                    $callback($code, $reason);
                    $callback($code, $reason);
                });
                return true;
            });

        return $this;
    }


    /**
     * Expect send with opcode and returned result.
     * @param int $opCode
     * @param bool $result If false then drain event emitted after 1 second
     * @return self
     */
    public function expectSend($opCode = Frame::OP_BINARY, $result = true): self
    {
        $this->webSocket
            ->expects(TestCase::once())
            ->method('send')
            ->willReturnCallback(function (Frame $frame) use ($opCode, $result) {
                Assert::assertEquals($opCode, $frame->getOpcode());
                Assert::assertEquals('test', (string) $frame);
                return $result;
            });

        if ($result) {
            return $this;
        }
        $this->webSocket
            ->expects(TestCase::atLeastOnce())
            ->method('on')
            ->willReturnCallback(function (string $event, callable $callback) {
                if ($event !== 'drain') {
                    return;
                }
                Loop::delay(1000, $callback);
            });

        return $this;
    }

    public function deferErrorEvent(): self
    {
        $this->webSocket
            ->expects(TestCase::atLeastOnce())
            ->method('on')
            ->willReturnCallback(function (string $event, callable $callback) {
                if ($event !== 'error') {
                    return;
                }
                $callback(new \Exception('test'));
            });

        return $this;
    }

    public function deferMessageEvent(): self
    {
        $this->webSocket
            ->expects(TestCase::atLeastOnce())
            ->method('on')
            ->willReturnCallback(function (string $event, callable $callable) {
                if ($event !== 'message') {
                    return;
                }
                Loop::defer(function () use ($callable) {
                    $message = new Message();
                    $message->addFrame(new Frame('test'));
                    $callable($message);
                });
            });
        return $this;
    }
}
