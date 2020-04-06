<?php

namespace ekstazi\websocket\stream\pawl;

use Amp\Promise;
use ekstazi\websocket\stream\pawl\adapters\Reader;
use ekstazi\websocket\stream\pawl\adapters\Writer;
use ekstazi\websocket\stream\Stream;

class Connection implements Stream
{
    /**
     * @var Reader
     */
    private $reader;
    /**
     * @var Writer
     */
    private $writer;

    public function __construct(Reader $reader, Writer $writer)
    {
        $this->reader = $reader;
        $this->writer = $writer;
    }

    /**
     * @inheritDoc
     */
    public function read(): Promise
    {
        return $this->reader->read();
    }

    /**
     * @inheritDoc
     */
    public function write(string $data): Promise
    {
        return $this->writer->write($data);
    }

    /**
     * @inheritDoc
     */
    public function end(string $finalData = ""): Promise
    {
        return $this->writer->end($finalData);
    }
}
