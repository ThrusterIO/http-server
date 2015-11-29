# HttpServer Component

[![Latest Version](https://img.shields.io/github/release/ThrusterIO/http-server.svg?style=flat-square)]
(https://github.com/ThrusterIO/http-server/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)]
(LICENSE)
[![Build Status](https://img.shields.io/travis/ThrusterIO/http-server.svg?style=flat-square)]
(https://travis-ci.org/ThrusterIO/http-server)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/ThrusterIO/http-server.svg?style=flat-square)]
(https://scrutinizer-ci.com/g/ThrusterIO/http-server)
[![Quality Score](https://img.shields.io/scrutinizer/g/ThrusterIO/http-server.svg?style=flat-square)]
(https://scrutinizer-ci.com/g/ThrusterIO/http-server)
[![Total Downloads](https://img.shields.io/packagist/dt/thruster/http-server.svg?style=flat-square)]
(https://packagist.org/packages/thruster/http-server)

[![Email](https://img.shields.io/badge/email-team@thruster.io-blue.svg?style=flat-square)]
(mailto:team@thruster.io)

The Thruster HttpServer Component.


## Install

Via Composer

``` bash
$ composer require thruster/http-server
```


## Usage

HTTP Server accepts Thruster Socket Server, `ServerApplicationInteface` implementing object and EventLoop

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Thruster\Component\EventLoop\EventLoop;
use Thruster\Component\Socket\Server as SocketServer;
use Thruster\Component\HttpServer\Server as HttpServer;
use Thruster\Component\ServerApplication\ServerApplicationInterface;


$application = new class implements ServerApplicationInterface { ... };

$loop   = new EventLoop();
$socket = new SocketServer($loop);
$server = new HttpServer($application, $socket, $loop);

$socket->listen(1337, '0.0.0.0');
$loop->run(); 
```

## Testing

``` bash
$ composer test
```


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.


## License

Please see [License File](LICENSE) for more information.
