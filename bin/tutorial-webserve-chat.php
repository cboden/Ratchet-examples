<?php
use Ratchet\Tutorials\Chat;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

    require dirname(__DIR__) . '/vendor/autoload.php';

    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new Chat()
            )
        )
      , 8080
    );

    $server->run();