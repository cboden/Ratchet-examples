<?php
use Ratchet\Component\Server\IOServerComponent;
use Ratchet\Examples\Tutorial\Chat;
use Ratchet\Examples\Cookbook\MessageLogger;
use Monolog\Logger;

    require dirname(__DIR__) . '/vendor/autoload.php';

    $stdout = new \Monolog\Handler\StreamHandler('php://stdout');
    $logout = new Logger('SockOut');
    $login  = new Logger('Sock-In');
    $login->pushHandler($stdout);
    $logout->pushHandler($stdout);

    $server = new IOServerComponent(
        new MessageLogger(
            new Chat()
          , $login
          , $logout
        )
    );

    $server->run(8000);
