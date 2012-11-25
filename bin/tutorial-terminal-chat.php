<?php
use Ratchet\Server\IoServer;
use Ratchet\Tutorials\Chat;

    require dirname(__DIR__) . '/vendor/autoload.php';

    $server = IoServer::factory(
        new Chat()
      , 8080
    );

    $server->run();
