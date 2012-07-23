<?php
// Dear FIG: Thank you for PSR-0!
use Ratchet\Server\IoServer;
use Ratchet\Server\FlashPolicy;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\ServerProtocol;

use React\EventLoop\Factory;
use React\Socket\Server as Reactor;
use React\ZMQ\Context;

use Ratchet\Website\Bot;
use Ratchet\Website\ChatRoom;
use Ratchet\Website\PortLogger;
use Ratchet\Cookbook\NullComponent;
use Ratchet\Cookbook\MessageLogger;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

    // Composer: The greatest thing since sliced bread
    require dirname(__DIR__) . '/vendor/autoload.php';

    // Setup logging
    $stdout = new StreamHandler('php://stdout');
    $logout = new Logger('SockOut');
    $login  = new Logger('Sock-In');
    $login->pushHandler($stdout);
    $logout->pushHandler($stdout);

    // The all mighty event loop
    $loop = Factory::create();

    // This little thing is to check for connectivity...
    // As a case study, when people connect on port 80, we're having them
    //  also connect on port 9000 and increment a counter if they connect.
    // Later, we can publish the results and find out if WebSockets over 
    //  a port other than 80 is viable (theory is blocked by firewalls).
    $context = new Context($loop);
    $push = $context->getSocket(ZMQ::SOCKET_PUSH);
    $push->connect('tcp://127.0.0.1:5555');

    // Setup our Ratchet ChatRoom application
    $webSock = new Reactor($loop);
    $webSock->listen(80, '0.0.0.0');
    $webServer = new IoServer(           // Basic I/O with clients, aww yeah
        new WsServer(                    // Boom! WebSockets
            new PortLogger($push, 80,    // Compare vs the almost over 9000 conns
                new MessageLogger(       // Log events in case of "oh noes"
                    new ServerProtocol(  // WAMP; the new hotness sub-protocol
                        new Bot(         // People kept asking me if I was a bot, so I made one!
                            new ChatRoom // ...and DISCUSS!
                        )
                    )
                  , $login
                  , $logout
                )
            )
        )
      , $webSock
    );

    // Allow Flash sockets (Internet Explorer) to connect to our app
    $flashSock = new Reactor($loop);
    $flashSock->listen(843, '0.0.0.0');
    $policy = new FlashPolicy;
    $policy->addAllowedAccess('*', 80);
    $webServer = new IoServer($policy, $flashSock);

    $logSock = new Reactor($loop);
    $logSock->listen(9000, '0.0.0.0');
    $zLogger = new IoServer(
        new WsServer(
            new PortLogger($push, 9000, new NullComponent)
        )
      , $logSock
    );

    // GO GO GO!
    $loop->run();
