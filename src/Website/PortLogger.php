<?php
namespace Ratchet\Website;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\WebSocket\WsServerInterface;
use React\ZMQ\SocketWrapper;

class PortLogger implements MessageComponentInterface, WsServerInterface {
    protected $pusher;
    protected $port;
    protected $app;

    public function __construct(SocketWrapper $pusher, $port, MessageComponentInterface $app) {
        $this->pusher = $pusher;
        $this->port   = (int)$port;
        $this->app    = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $conn) {
        $this->pusher->send($this->port);

        $this->app->onOpen($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        $this->app->onMessage($from, $msg);
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $conn) {
        $this->app->onClose($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->app->onError($conn, $e);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubProtocols() {
        if ($this->app instanceof WsServerInterface) {
            return $this->app->getSubProtocols();
        } else {
            return array();
        }
    }
}