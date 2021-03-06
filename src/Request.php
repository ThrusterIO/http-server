<?php

namespace Thruster\Component\HttpServer;

use Psr\Http\Message\ResponseInterface;
use Thruster\Component\Http\RequestParser;
use Thruster\Component\EventEmitter\EventEmitterInterface;
use Thruster\Component\EventEmitter\EventEmitterTrait;
use Thruster\Component\Socket\ConnectionInterface;

/**
 * Class Request
 *
 * @package Thruster\Component\HttpServer
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Request implements EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var bool
     */
    private $closed;

    /**
     * @var bool
     */
    private $writable;

    /**
     * @var bool
     */
    private $headWritten;

    /**
     * @var bool
     */
    private $chunkedEncoding;

    /**
     * @var RequestParser
     */
    private $requestParser;

    public function __construct(ConnectionInterface $connection, array $options = [])
    {
        $this->connection = $connection;

        $this->requestParser = new RequestParser($options);

        $this->chunkedEncoding = true;
        $this->closed          = false;
        $this->headWritten     = false;

        $this->requestParser->on('request', function () {
            $this->emit('request', func_get_args());
        });

        $this->requestParser->on('received_head', function () {
            $this->emit('received_head', func_get_args());
        });

        $connection->on('end', function () {
            $this->close();
        });

        $connection->on('error', function ($error) {
            $this->emit('error', [$error, $this]);
            $this->close();
        });

        $connection->on('drain', function () {
            $this->emit('drain');
        });
    }

    public function feed($data)
    {
        $this->requestParser->onData($data);
    }

    public function isWritable() : bool
    {
        return $this->writable;
    }

    public function sendResponse(ResponseInterface $response)
    {
        $headers = [];

        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }


        $response->getBody()->rewind();
        $size = $response->getBody()->getSize();

        $this->writeHead($size > 0, $response->getStatusCode(), $response->getReasonPhrase(), $headers);


        $this->end($response->getBody()->getContents());
    }

    public function writeHead(bool $hasBody, int $status, string $reasonPhrase, array $headers)
    {
        if (true === $this->headWritten) {
            throw new \Exception('Response head has already been written.');
        }

        if (true === isset($headers['Content-Length']) || false === $hasBody) {
            $this->chunkedEncoding = false;
        }

        if (true === $this->chunkedEncoding) {
            $headers['Transfer-Encoding'] = 'chunked';
        }

        $data = $this->formatHead($status, $reasonPhrase, $headers);
        $this->connection->write($data);

        $this->headWritten = true;
    }

    private function formatHead($status, $reasonPhrase, array $headers)
    {
        $status = (int)$status;
        $data   = "HTTP/1.1 $status $reasonPhrase\r\n";

        foreach ($headers as $name => $value) {
            $name = str_replace(["\r", "\n"], '', $name);

            foreach ((array)$value as $val) {
                $val = str_replace(["\r", "\n"], '', $val);

                $data .= "$name: $val\r\n";
            }
        }

        $data .= "\r\n";

        return $data;
    }

    public function write($data)
    {
        if (false === $this->headWritten) {
            throw new \Exception('Response head has not yet been written.');
        }

        if (true === $this->chunkedEncoding) {
            $len     = strlen($data);
            $chunk   = dechex($len) . "\r\n" . $data . "\r\n";
            $flushed = $this->connection->write($chunk);
        } else {
            $flushed = $this->connection->write($data);
        }

        return $flushed;
    }

    public function end($data = null)
    {
        if (null !== $data) {
            $this->write($data);
        }

        if (true === $this->chunkedEncoding) {
            $this->connection->write("0\r\n\r\n");
        }

        $this->emit('end');
        $this->removeListeners();
        $this->connection->end();
    }

    public function close()
    {
        if (true === $this->closed) {
            return;
        }

        $this->closed   = true;
        $this->writable = false;
        $this->emit('close');
        $this->removeListeners();

        $this->connection->close();
    }
}
