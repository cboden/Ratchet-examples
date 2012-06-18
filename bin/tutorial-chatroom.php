<?php
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;
use Ratchet\Examples\Tutorial\ChatRoom;
use Ratchet\Examples\Cookbook\MessageLogger;
use Monolog\Logger;

    require dirname(__DIR__) . '/vendor/autoload.php';

    $stdout = new \Monolog\Handler\StreamHandler('php://stdout');
    $logout = new Logger('SockOut');
    $login  = new Logger('Sock-In');
    $login->pushHandler($stdout);
    $logout->pushHandler($stdout);

    $server = IoServer::factory(
        new WsServer(
            new MessageLogger(
                new WampServer(
                    new ChatRoom
                )
              , $login
              , $logout
            )
        )
      , 80
    );

    $server->run();
