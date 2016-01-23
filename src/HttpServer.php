<?php

namespace Thruster\Component\HttpServer;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Thruster\Component\Socket\Server;
use Thruster\Component\Socket\Connection;
use Thruster\Component\Socket\ServerInterface;
use Thruster\Component\Socket\ConnectionInterface;
use Thruster\Component\Promise\PromiseInterface;
use Thruster\Component\HttpServer\ResponseModifier\DateModifier;
use Thruster\Component\HttpServer\ResponseModifier\PoweredByModifier;
use Thruster\Component\ServerApplication\ServerApplicationInterface;

/**
 * Class HttpServer
 *
 * @package Thruster\Component\HttpServer
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class HttpServer
{
    const VERSION = '0.2';

    /**
     * @var ServerApplicationInterface
     */
    private $application;

    /**
     * @var Server[]
     */
    private $servers;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var ResponseModifierInterface[]
     */
    private $responseModifiers;

    /**
     * @var int
     */
    private $servedRequests;

    /**
     * @var int
     */
    private $failedRequests;

    /**
     * @var int
     */
    private $upTime;

    private function __construct(ServerApplicationInterface $application)
    {
        $this->application    = $application;
        $this->servers        = [];
        $this->debug          = false;
        $this->servedRequests = 0;
        $this->failedRequests = 0;
        $this->upTime         = time();

        $this->responseModifiers = [
            new DateModifier(),
            new PoweredByModifier('Thruster/' . static::VERSION),
        ];
    }

    public static function create(ServerApplicationInterface $application) : self
    {
        return new static($application);
    }

    public function attachTo(ServerInterface $server) : self
    {
        if (false !== array_search($server, $this->servers, true)) {
            return $this;
        }

        $this->servers[] = $server;
        $this->listenTo($server);

        return $this;
    }

    public function withResponseModifier(ResponseModifierInterface $modifier) : self
    {
        if (false !== array_search($modifier, $this->responseModifiers, true)) {
            return $this;
        }

        $this->responseModifiers[] = $modifier;

        return $this;
    }

    public function enableDebug() : self
    {
        $this->debug = true;

        return $this;
    }

    public function getStatistics() : array
    {
        return [
            'served_requests' => $this->servedRequests,
            'failed_requests' => $this->failedRequests,
            'up_time'         => (time() - $this->upTime),
        ];
    }

    private function listenTo(ServerInterface $server, array $options = [])
    {
        $server->on(
            'connection',
            function (ConnectionInterface $connection) use ($options) {
                $request = new Request($connection, $options);

                $request->on(
                    'received_head',
                    function ($headers, $httpMethod, $uri, $protocolVersion) use ($request, $connection) {
                        $this->servedRequests++;

                        $response = $this->application->processHead($headers, $httpMethod, $uri, $protocolVersion);

                        if (null !== $response) {
                            $connection->removeListener('data', [$request, 'feed']);

                            $response = $this->modifyResponse($response);

                            $request->sendResponse($response);
                        }
                    }
                );

                $request->on(
                    'request',
                    function (ServerRequestInterface $serverRequest) use ($request, $connection) {

                        $connection->removeListener('data', [$request, 'feed']);

                        $fulfilled = function (ResponseInterface $response) use ($request) {
                            $response = $this->modifyResponse($response);
                            $request->sendResponse($response);
                        };

                        $rejected = function (\Throwable $exception) use ($request) {
                            $this->failedRequests++;

                            $response = new Response(500);
                            if (true === $this->debug) {
                                $error = [
                                    'class' => get_class($exception),
                                    'message' => $exception->getMessage(),
                                    'code' => $exception->getCode(),
                                    'file' => $exception->getFile(),
                                    'line' => $exception->getLine()
                                ];

                                $response = $response->withHeader('Content-Type', 'application/json');
                                $response->getBody()->write(json_encode($error));
                            }

                            $response = $this->modifyResponse($response);
                            $request->sendResponse($response);
                        };

                        $this->application->processRequest($serverRequest)->done($fulfilled, $rejected);
                    }
                );

                $connection->on('data', [$request, 'feed']);
            }
        );
    }

    private function modifyResponse(ResponseInterface $response) : ResponseInterface
    {
        foreach ($this->responseModifiers as $responseModifier) {
            $response = $responseModifier->modify($response);
        }

        return $response;
    }
}
