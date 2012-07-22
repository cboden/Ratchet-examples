<?php
use Ratchet\Server\IoServer;
use Ratchet\Examples\Tutorial\Chat;

    require dirname(__DIR__) . '/vendor/autoload.php';

    $server = IoServer::factory(
        new Chat()
      , 8000
    );

    $server->run();
