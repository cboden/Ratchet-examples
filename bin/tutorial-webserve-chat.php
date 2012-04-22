<?php
use Ratchet\Examples\Tutorial\Chat;
use Ratchet\Component\Server\IOServerComponent;
use Ratchet\Component\WebSocket\WebSocketComponent;

    require dirname(__DIR__) . '/vendor/autoload.php';

    $server = new IOServerComponent(
        new WebSocketComponent(
            new Chat()
        )
    );

    $server->run(8000);