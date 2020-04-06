<?php

namespace ekstazi\websocket\stream\pawl\test;

use Amp\PHPUnit\AsyncTestCase;
use Amp\Success;
use ekstazi\websocket\stream\pawl\adapters\Reader;
use ekstazi\websocket\stream\pawl\adapters\Writer;
use ekstazi\websocket\stream\pawl\Connection;

class ConnectionTest extends AsyncTestCase
{
    /**
     * Test that data readed from websocket client.
     * @return \Generator
     */
    public function testRead()
    {
        $reader = $this->createMock(Reader::class);
        $reader
            ->expects(self::once())
            ->method('read')
            ->willReturn(new Success('test'));

        $writer = $this->createMock(Writer::class);
        $connection = new Connection($reader, $writer);
        $data = yield $connection->read();
        self::assertEquals('test', $data);
    }



    /**
     * Test write method.
     * @return \Generator
     * @throws
     */
    public function testWrite()
    {
        $reader = $this->createMock(Reader::class);
        $writer = $this->createMock(Writer::class);
        $writer
            ->expects(self::once())
            ->method('write')
            ->with('test')
            ->willReturn(new Success());
        $connection = new Connection($reader, $writer);
        yield $connection->write('test');
    }

    /**
     * @return \Generator
     * @throws
     */
    public function testEnd()
    {
        $reader = $this->createMock(Reader::class);
        $writer = $this->createMock(Writer::class);
        $writer
            ->expects(self::once())
            ->method('end')
            ->with('test')
            ->willReturn(new Success());

        $connection = new Connection($reader, $writer);
        yield $connection->end('test');
    }
}
