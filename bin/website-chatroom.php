<?php
use Ratchet\Server\IoServer;
use Ratchet\Server\FlashPolicy;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\ServerProtocol;

use React\EventLoop\Factory;
use React\Socket\Server as Reactor;

use Ratchet\Website\Bot;
use Ratchet\Website\ChatRoom;
use Ratchet\Cookbook\MessageLogger;

use Monolog\Logger;

    require dirname(__DIR__) . '/vendor/autoload.php';

    // Setup logging
    $stdout = new \Monolog\Handler\StreamHandler('php://stdout');
    $logout = new Logger('SockOut');
    $login  = new Logger('Sock-In');
    $login->pushHandler($stdout);
    $logout->pushHandler($stdout);

    $loop = Factory::create();

    // Setup our ChatRoom Ratchet application
    $webSock = new Reactor($loop);
    $webSock->listen(80, '0.0.0.0');
    $webServer = new IoServer(
        new WsServer(
            new MessageLogger(
                new ServerProtocol(
                    new Bot(
                        new ChatRoom
                    )
                )
              , $login
              , $logout
            )
        )
      , $webSock
    );

    // Allow Flash sockets to connect to our app
    $flashSock = new Reactor($loop);
    $flashSock->listen(843, '0.0.0.0');
    $policy = new FlashPolicy;
    $policy->addAllowedAccess('*', 80);
    $webServer = new IoServer($policy, $flashSock);

    // GO GO GO!
    $loop->run();
