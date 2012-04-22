<?php
use Ratchet\Component\Server\IOServerComponent;
use Ratchet\Examples\Tutorial\Chat;

    require dirname(__DIR__) . '/vendor/autoload.php';


    $server = new IOServerComponent(
        new Chat()
    );

    $server->run(8000);
