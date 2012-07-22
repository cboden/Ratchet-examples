<?php
namespace Ratchet\Website;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

class ChatRoom implements WampServerInterface {
    const CTRL_PREFIX = 'ctrl:';
    const CTRL_ROOMS  = 'ctrl:rooms';

    protected $rooms = array();

    protected $roomLookup = array();

    public function __construct() {
        $this->rooms[static::CTRL_ROOMS] = new \SplObjectStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $conn) {
        $conn->Chat        = new \StdClass;
        $conn->Chat->rooms = array();
        $conn->Chat->name  = $conn->WAMP->sessionId;

        if (isset($conn->WebSocket)) {
            $conn->Chat->name = $this->escape($conn->WebSocket->request->getCookie('name'));

            if (empty($conn->Chat->name)) {
                $conn->Chat->name  = 'Anonymous ' . $conn->resourceId;
            }
        } else {
            $conn->Chat->name  = 'Anonymous ' . $conn->resourceId;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $conn) {
        foreach ($conn->Chat->rooms as $topic => $one) {
            $this->onUnSubscribe($conn, $topic);
        }
    }

    /**
     * {@inheritdoc}
     */
    function onCall(ConnectionInterface $conn, $id, $fn, array $params) {
        switch ($fn) {
            case 'setName':
            break;

            case 'createRoom':
                $topic   = $this->escape($params[0]);
                $created = false;

                if (empty($topic)) {
                    return $conn->callError($id, 'Room name can not be empty');
                }

                if (array_key_exists($topic, $this->roomLookup)) {
                    $roomId = $this->roomLookup[$topic];
                } else {
                    $created = true;
                    $roomId  = uniqid('room-');

                    $this->broadcast(static::CTRL_ROOMS, array($roomId, $topic, 1));
                }

                if ($created) {
                    $this->rooms[$roomId] = new \SplObjectStorage;
                    $this->roomLookup[$topic] = $roomId;

                    return $conn->callResult($id, array('id' => $roomId, 'display' => $topic));
                } else {
                    return $conn->callError($id, array('id' => $roomId, 'display' => $topic));
                }
            break;

            default:
                return $conn->callError($id, 'Unknown call');
            break;
        }
    }

    /**
     * {@inheritdoc}
     */
    function onSubscribe(ConnectionInterface $conn, $topic) {
        // Send all the rooms to the person who just subscribed to the room list
        if (static::CTRL_ROOMS == $topic) {
            foreach ($this->rooms as $room => $patrons) {
                if (!$this->isControl($room)) {
                    $conn->event(static::CTRL_ROOMS, array($room, array_search($room, $this->roomLookup), 1));
                }
            }
        }

        // Room does not exist
        if (!array_key_exists($topic, $this->rooms)) {
            return;
        }

        // Notify everyone this guy has joined the room they're in
        $this->broadcast($topic, array('joinRoom', $conn->WAMP->sessionId, $conn->Chat->name), $conn);

        // List all the people already in the room to the person who just joined
        foreach ($this->rooms[$topic] as $patron) {
            $conn->event($topic, array('joinRoom', $patron->WAMP->sessionId, $patron->Chat->name));
        }

        $this->rooms[$topic]->attach($conn);

        $conn->Chat->rooms[$topic] = 1;
    }

    /**
     * {@inheritdoc}
     */
    function onUnSubscribe(ConnectionInterface $conn, $topic) {
        unset($conn->Chat->rooms[$topic]);
        $this->rooms[$topic]->detach($conn);

        if ($this->isControl($topic)) {
            return;
        }

        if ($this->rooms[$topic]->count() == 0) {
            unset($this->rooms[$topic], $this->roomLookup[array_search($topic, $this->roomLookup)]);
            $this->broadcast(static::CTRL_ROOMS, array($topic, 0));
        } else {
            $this->broadcast($topic, array('leftRoom', $conn->WAMP->sessionId));
        }
    }

    /**
     * {@inheritdoc}
     */
    function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude = array(), array $eligible = array()) {
        $event = (string)$event;
        if (empty($event)) {
            return;
        }

        if (!array_key_exists($topic, $conn->Chat->rooms) || !array_key_exists($topic, $this->rooms) || $this->isControl($topic)) {
            // error, can not publish to a room you're not subscribed to
            // not sure how to handle error - WAMP spec doesn't specify
            // for now, we're going to silently fail

            return;
        }

        $event = $this->escape($event);

        $this->broadcast($topic, array('message', $conn->WAMP->sessionId, $event, date('c')));
    }

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }

    protected function broadcast($topic, $msg, ConnectionInterface $exclude = null) {
        foreach ($this->rooms[$topic] as $client) {
            if ($client !== $exclude) {
                $client->event($topic, $msg);
            }
        }
    }

    /**
     * @param string
     * @return boolean
     */
    protected function isControl($room) {
        return (boolean)(substr($room, 0, strlen(static::CTRL_PREFIX)) == static::CTRL_PREFIX);
    }

    /**
     * @param string
     * @return string
     */
    protected function escape($string) {
        return htmlspecialchars($string);
    }
}