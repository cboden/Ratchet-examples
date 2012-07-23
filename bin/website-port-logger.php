<?php

    $root = dirname(__DIR__);
    require $root . '/vendor/autoload.php';

    try {
        $db = new PDO("sqlite:{$root}/reports/portLog.sqlite");
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
        $db->prepare("INSERT OR IGNORE INTO portCounter VALUES (?, 0)")->execute(array((int)$port));
        $db->prepare("UPDATE portCounter SET count = count + 1 WHERE port LIKE ?")->execute(array((int)$port));
    });

    $loop->run();
