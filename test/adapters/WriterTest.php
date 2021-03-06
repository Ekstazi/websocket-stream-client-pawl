<?php

namespace ekstazi\websocket\client\pawl\test\adapters;

use Amp\ByteStream\OutputStream;
use Amp\ByteStream\StreamException;
use Amp\Delayed;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Success;
use ekstazi\websocket\client\pawl\adapters\Writer;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Frame;

class WriterTest extends AsyncTestCase
{
    public function testConstruct()
    {
        $client = $this->stubWebsocket();

        $reader = new Writer($client);
        self::assertInstanceOf(OutputStream::class, $reader);
    }

    /**
     * @dataProvider writeModes
     * @param string $mode
     * @param int $expectedOpCode
     * @throws \Amp\ByteStream\ClosedException
     * @throws \Amp\ByteStream\StreamException
     * @throws \ReflectionException
     */
    public function testWriteImmediate(string $mode, int $expectedOpCode)
    {
        $builder = new WebsocketBuilder($this->stubWebsocket());
        $builder->expectSend($expectedOpCode);

        $writer = new Writer($builder->build(), $mode);
        $promise = $writer->write('test');
        self::assertInstanceOf(Success::class, $promise);
    }

    /**
     * @dataProvider writeModes
     * @param string $mode
     * @param int $expectedOpCode
     *
     * @return \Generator
     * @throws
     */
    public function testWriteBackpressure(string $mode, int $expectedOpCode)
    {
        $builder = new WebsocketBuilder($this->stubWebsocket());
        $builder->expectSend($expectedOpCode, false);

        $writer = new Writer($builder->build());

        $startTime = \microtime(true);
        yield $writer->write('test', $mode);
        $endTime = \microtime(true);

        self::assertGreaterThan(1, $endTime - $startTime);
    }

    public function testWriteError()
    {
        $builder = new WebsocketBuilder($this->stubWebsocket());
        $builder->deferErrorEvent();

        $writer = new Writer($builder->build());

        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('test');
        yield $writer->write('test');
    }

    public function testWriteAfterClose()
    {
        $builder = new WebsocketBuilder($this->stubWebsocket());
        $builder->deferCloseEvent();

        $writer = new Writer($builder->build());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The stream was closed. ');
        yield new Delayed(0);
        yield $writer->write('test');
    }

    public function writeModes()
    {
        return [
            'binary' => [Writer::MODE_BINARY, Frame::OP_BINARY],
            'text' => [Writer::MODE_TEXT, Frame::OP_TEXT],
        ];
    }

    public function testEndSuccess()
    {
        $builder = new WebsocketBuilder($this->stubWebsocket());
        $client = $builder
            ->deferCloseEvent()
            ->build();

        $writer = new Writer($client);
        yield $writer->end();
    }

    public function testEndCloseError()
    {
        $builder = new WebsocketBuilder($this->stubWebsocket());
        $client = $builder
            ->deferCloseEvent(Frame::CLOSE_ABNORMAL, 'error')
            ->build();

        $writer = new Writer($client);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('error');
        $this->expectExceptionCode(Frame::CLOSE_ABNORMAL);

        yield $writer->end();
    }

    public function testEndAfterError()
    {
        $builder = new WebsocketBuilder($this->stubWebsocket());
        $builder->deferErrorEvent();

        $writer = new Writer($builder->build());
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('test');

        yield $writer->end();
    }

    public function testEndWithData()
    {
        $builder = new WebsocketBuilder($this->stubWebsocket());
        $client = $builder
            ->deferCloseEvent()
            ->expectSend()
            ->build();

        $writer = new Writer($client);
        yield $writer->end('test');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|WebSocket
     */
    private function stubWebsocket()
    {
        return $this->createMock(WebSocket::class);
    }
}
