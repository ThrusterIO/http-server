<?php

namespace Thruster\Component\HttpServer;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface ResponseModifierInterface
 *
 * @package Thruster\Component\HttpServer
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
interface ResponseModifierInterface
{
    public function modify(ResponseInterface $response) : ResponseInterface;
}
