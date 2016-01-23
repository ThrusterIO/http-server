<?php

namespace Thruster\Component\HttpServer\ResponseModifier;

use Psr\Http\Message\ResponseInterface;
use Thruster\Component\HttpServer\HttpServer;
use Thruster\Component\HttpServer\ResponseModifierInterface;

/**
 * Class PoweredByModifier
 *
 * @package Thruster\Component\HttpServer\ResponseModifier
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class PoweredByModifier implements ResponseModifierInterface
{
    /**
     * @var string
     */
    private $poweredBy;

    public function __construct(string $poweredBy)
    {
        $this->poweredBy = $poweredBy;
    }

    public function modify(ResponseInterface $response) : ResponseInterface
    {
        return $response->withHeader('X-Powered-By', $this->poweredBy);
    }
}
