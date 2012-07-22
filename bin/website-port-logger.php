<?php

    $root = dirname(__DIR__);
    require $root . '/vendor/autoload.php';

    try {
        $db = new PDO("sqlite::file://{$root}/portLog.sqlite");
    } catch (PDOException $pe) {
        die($pe->getMessage() . "\n");
    }

    $loop    = React\EventLoop\Factory::create();
    $context = new React\ZMQ\Context($loop);

    $pull = $context->getSocket(ZMQ::SOCKET_PULL);
    $pull->bind('tcp://127.0.0.1:5555');

    $pull->on('error', function ($e) {
        echo "Error: {$e->getMessage()}\n";
    });

    $pull->on('message', function ($port) use ($db) {
        $port = (int)$port;

        // untested
        $db->exec("INSERT INTO `portCounter` (`port`, `count`) VALUES ({$port}, 0) ON DUPLICATE KEY UPDATE `count` = `count` + 1 WHERE `port` = {$port}");
    });

    $loop->run();
