<?php
use Ratchet\Server\IoServer;
use Ratchet\Tutorials\Chat;
use Ratchet\Cookbook\MessageLogger;
use Monolog\Logger;

    require dirname(__DIR__) . '/vendor/autoload.php';

    $stdout = new \Monolog\Handler\StreamHandler('php://stdout');
    $logout = new Logger('SockOut');
    $login  = new Logger('Sock-In');
    $login->pushHandler($stdout);
    $logout->pushHandler($stdout);

    $server = IoServer::factory(
        new MessageLogger(
            new Chat()
          , $login
          , $logout
        )
      , 8080
    );

    $server->run();
