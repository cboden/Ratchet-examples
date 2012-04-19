<?php
use Ratchet\Component\Server\IOServerComponent;
use Ratchet\Examples\Tutorial\Chat;

    require dirname(__DIR__) . '/vendor/.composer/autoload.php';


    $server = new IOServerComponent(
        new Chat()
    );

    $server->run(new \Ratchet\Resource\Socket\BSDSocket, '0.0.0.0', 8000);

// Below is what Ratchet will have it set to soon
//    $server->run(8000);
