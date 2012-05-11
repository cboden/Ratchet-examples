<?php
use Ratchet\Examples\Tutorial\Chat;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

    require dirname(__DIR__) . '/vendor/autoload.php';

    $server = IoServer::factory(
        new WsServer(
            new Chat()
        )
      , 8000
    );

    $server->run();