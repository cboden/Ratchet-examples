<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;
use Ratchet\Cookbook\OpenPubSub;

    require dirname(__DIR__) . '/vendor/autoload.php';

    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new WampServer(
                    new OpenPubSub
                )
            )
        )
      , 8000
    );

    $server->run();