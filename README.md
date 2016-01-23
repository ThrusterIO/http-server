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
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Thruster\Component\EventLoop\EventLoop;
use Thruster\Component\Socket\Server;
use Thruster\Component\HttpServer\HttpServer;
use Thruster\Component\ServerApplication\SynchronousServerApplication;


$application = new class extends SynchronousServerApplication {
    /**
     * {@inheritDoc}
     */
    public function processRequestSynchronously(ServerRequestInterface $request) : ResponseInterface
    {
        $response = new Response(200);
        $response->getBody()->write('Hello World!');

        return $response;
    }

    public function preloadApplication()
    {
    }
};

$loop   = new EventLoop();
$socket = new Server($loop);

$httpServer = HttpServer::create($application)
    ->attachTo($socket)
    ->enableDebug();

$socket->listen(1337, '0.0.0.0');

$loop->run();
```

### Benchmark results

```
Server Software:
Server Hostname:        127.0.0.1
Server Port:            1337

Document Path:          /
Document Length:        22 bytes

Concurrency Level:      10
Time taken for tests:   2.148 seconds
Complete requests:      10000
Failed requests:        0
Total transferred:      1340000 bytes
HTML transferred:       220000 bytes
Requests per second:    4655.03 [#/sec] (mean)
Time per request:       2.148 [ms] (mean)
Time per request:       0.215 [ms] (mean, across all concurrent requests)
Transfer rate:          609.15 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.0      0       0
Processing:     1    2   0.9      2      11
Waiting:        1    2   0.9      2      11
Total:          1    2   0.9      2      11

Percentage of the requests served within a certain time (ms)
  50%      2
  66%      2
  75%      2
  80%      2
  90%      3
  95%      3
  98%      5
  99%      7
 100%     11 (longest request)
```

## Testing

``` bash
$ composer test
```


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.


## License

Please see [License File](LICENSE) for more information.
