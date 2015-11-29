<?php

namespace Thruster\Component\HttpServer;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Thruster\Component\EventEmitter\EventEmitterInterface;
use Thruster\Component\EventEmitter\EventEmitterTrait;
use Thruster\Component\EventLoop\EventLoopInterface;
use Thruster\Component\Http\RequestParser;
use Thruster\Component\Socket\ServerInterface;
use Thruster\Component\Socket\Connection as SocketConnection;
use Thruster\Component\ServerApplication\ServerApplicationInterface;

/**
 * Class Server
 *
 * @package Thruster\Component\HttpServer
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Server implements EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @var EventLoopInterface
     */
    protected $loop;

    /**
     * @var ServerInterface
     */
    protected $server;

    /**
     * @var ServerApplicationInterface
     */
    protected $application;

    /**
     * @var int
     */
    protected $servedRequest;

    /**
     * @var int
     */
    protected $upTime;

    /**
     * @param ServerApplicationInterface $application
     * @param ServerInterface            $server
     * @param EventLoopInterface         $loop
     */
    public function __construct(
        ServerApplicationInterface $application,
        ServerInterface $server,
        EventLoopInterface $loop
    ) {
        $this->application   = $application;
        $this->loop          = $loop;
        $this->servedRequest = 0;
        $this->upTime        = time();

        $this->initializeServer($server);
    }

    public function initializeServer(ServerInterface $server)
    {
        $server->on(
            'connection',
            function (SocketConnection $socketConnection) {
                $connection = $this->getConnection($socketConnection);

                $connection->on(
                    'received_head',
                    function ($headers, $httpMethod, $uri, $protocolVersion) use ($connection, $socketConnection) {
                        $this->servedRequest++;

                        $response = $this->application->processHead($headers, $httpMethod, $uri, $protocolVersion);

                        if (null !== $response) {
                            $socketConnection->removeListener('data', [$connection, 'feed']);

                            $response = $this->modifyResponse($response);

                            $connection->sendResponse($response);
                        }
                    }
                );

                $connection->on(
                    'request',
                    function (ServerRequestInterface $request) use ($socketConnection, $connection) {
                        $socketConnection->removeListener('data', [$connection, 'feed']);

                        $response = $this->application->processRequest($request);
                        $response = $this->modifyResponse($response);

                        $connection->sendResponse($response);
                    }
                );

                $socketConnection->on('data', [$connection, 'feed']);
            }
        );

        $this->server = $server;
    }

    /**
     * @param SocketConnection $connection
     *
     * @return Connection
     */
    public function getConnection(SocketConnection $connection) : Connection
    {
        return new Connection($connection, $this->loop);
    }

    /**
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function modifyResponse(ResponseInterface $response) : ResponseInterface
    {
        return $response
            ->withHeader('Date', gmdate('D, d M Y H:i:s T'))
            ->withHeader('X-Powered-By', 'Thruster/1.0');
    }

    /**
     * Returns seconds online
     *
     * @return int
     */
    public function getUpTime() : int
    {
        return time() - $this->upTime;
    }

    /**
     * Returns total request served
     *
     * @return int
     */
    public function getServedRequests() : int
    {
        return $this->servedRequest;
    }
}
