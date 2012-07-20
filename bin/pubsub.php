<?php
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;

use Ratchet\Examples\Cookbook\OpenPubSub;
use Ratchet\Examples\Cookbook\MessageLogger;

use Monolog\Logger;

    require dirname(__DIR__) . '/vendor/autoload.php';

    error_reporting(E_ALL | E_STRICT);
    ini_set('display_errors', 1);

    // Setup logging
    $stdout = new \Monolog\Handler\StreamHandler('php://stdout');
    $logout = new Logger('SockOut');
    $login  = new Logger('Sock-In');
    $login->pushHandler($stdout);
    $logout->pushHandler($stdout);

    $server = IoServer::factory(
        new WsServer(
            new MessageLogger(
                new WampServer(
                    new OpenPubSub
                )
              , $login
              , $logout
            )
        )
      , 8000
    );

    $server->run();