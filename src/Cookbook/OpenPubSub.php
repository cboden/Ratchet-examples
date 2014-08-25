<?php
namespace Ratchet\Cookbook;
use Ratchet\ConnectionInterface as Conn;
use Ratchet\Wamp\WampServerInterface;

/**
 * A simple pub/sub implementation
 * Anything clients publish on a topic will be received
 *  on that topic by all clients
 */
class OpenPubSub implements WampServerInterface {
    public function onPublish(Conn $conn, $topic, $event, array $exclude = array(), array $eligible = array()) {
        $topic->broadcast($event);
    }

    public function onCall(Conn $conn, $id, $topic, array $params) {
        $conn->callError($id, $topic, 'RPC not supported');
    }

    public function onOpen(Conn $conn) {
    }

    public function onClose(Conn $conn) {
    }

    public function onSubscribe(Conn $conn, $topic) {
    }

    public function onUnSubscribe(Conn $conn, $topic) {
    }

    public function onError(Conn $conn, \Exception $e) {
    }
}