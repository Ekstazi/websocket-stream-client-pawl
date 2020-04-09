<?php

namespace ekstazi\websocket\client\pawl\test\adapters;

use Amp\ByteStream\InputStream;
use Amp\Delayed;
use Amp\PHPUnit\AsyncTestCase;
use ekstazi\websocket\client\pawl\adapters\Reader;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Frame;

class ReaderTest extends AsyncTestCase
{

    /**
     * Test that reader instance of InputStream.
     */
    public function testConstruct()
    {
        $client = $this->createMock(WebSocket::class);

        $reader = new Reader($client);
        $this->assertInstanceOf(InputStream::class, $reader);
    }

    public function testReadSuccess()
    {
        $builder = new WebsocketBuilder($this->createMock(WebSocket::class));
        $client = $builder
            ->deferMessageEvent()
            ->build();

        $reader = new Reader($client);
        $data = yield $reader->read();
        self::assertEquals('test', $data);
    }

    public function testReadBackpressure()
    {
        $builder = new WebsocketBuilder($this->createMock(WebSocket::class));
        $client = $builder
            ->deferMessageEvent()
            ->measureReadTime($time)
            ->build();

        $reader = new Reader($client);
        yield new Delayed(1000);
        $data = yield $reader->read();
        self::assertEquals('test', $data);
        self::assertGreaterThanOrEqual(-100, $time - 1000);
    }

    public function testReadCloseSuccess()
    {
        $builder = new WebsocketBuilder($this->createMock(WebSocket::class));
        $client = $builder
            ->deferCloseEvent()
            ->build();


        $reader = new Reader($client);
        $data = yield $reader->read();
        self::assertNull($data);
    }

    public function testReadCloseError()
    {
        $builder = new WebsocketBuilder($this->createMock(WebSocket::class));
        $client = $builder
            ->deferCloseEvent(Frame::CLOSE_ABNORMAL, 'test error')
            ->build();

        $reader = new Reader($client);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('test error');
        $this->expectExceptionCode(Frame::CLOSE_ABNORMAL);
        $data = yield $reader->read();
    }

    public function testReadError()
    {
        $builder = new WebsocketBuilder($this->createMock(WebSocket::class));
        $client = $builder->deferErrorEvent()
            ->build();


        $reader = new Reader($client);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('test');
        $data = yield $reader->read();
    }

    public function testReadCloseAfterCloseSuccess()
    {
        $builder = new WebsocketBuilder($this->createMock(WebSocket::class));
        $client = $builder->deferDoubleCloseEvent()
            ->build();

        $reader = new Reader($client);
        $data = yield $reader->read();
        self::assertNull($data);
    }
}
