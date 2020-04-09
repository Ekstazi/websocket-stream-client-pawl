<?php

namespace ekstazi\websocket\client\pawl\test;

use Amp\PHPUnit\AsyncTestCase;
use ekstazi\websocket\client\pawl\Connection;
use ekstazi\websocket\common\Reader;
use ekstazi\websocket\common\Writer;

class ConnectionTest extends AsyncTestCase
{
    public function testConstruct()
    {
        $connection = new Connection($this->createStub(Reader::class), $this->createStub(Writer::class));
        self::assertInstanceOf(\ekstazi\websocket\client\Connection::class, $connection);
    }
}
