<?php

namespace Thruster\Component\HttpServer\ResponseModifier;

use Psr\Http\Message\ResponseInterface;
use Thruster\Component\HttpServer\ResponseModifierInterface;

/**
 * Class DateModifier
 *
 * @package Thruster\Component\HttpServer\ResponseModifier
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class DateModifier implements ResponseModifierInterface
{
    public function modify(ResponseInterface $response) : ResponseInterface
    {
        return $response->withHeader('Date', gmdate('D, d M Y H:i:s T'));
    }
}
