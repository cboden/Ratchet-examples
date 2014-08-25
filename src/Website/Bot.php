<?php
namespace Ratchet\Website;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;
use Ratchet\Wamp\WampConnection;
use Guzzle\Http\Message\Request;

class Bot implements WampServerInterface {
    protected $app;

    protected $wampBot;
    protected $stubBot;

    public $roomId;

    protected $genCount  = 0;

    public function __construct(WampServerInterface $app) {
        $this->app = $app;

        $this->stubBot = new ConnectionStub;
        $this->wampBot = new WampConnection($this->stubBot);

        $this->wampBot->resourceId = -1;

        $this->wampBot->WebSocket = new \StdClass;
        $this->wampBot->WebSocket->request = new Request('get', '/');
        $this->wampBot->WebSocket->request->addCookie('name', 'Lonely Bot');

        $this->wampBot->WAMP = new \StdClass;
        $this->wampBot->WAMP->sessionId =  1;

        $that = $this;
        $this->stubBot->setSendCallback(function($msg) use ($that) {
            $response     = json_decode($msg, true);
            $that->roomId = $response[2]['id'];
        });

        $this->app->onOpen($this->wampBot);
        $this->app->onCall($this->wampBot, '1', 'createRoom', array('General'));
        $this->stubBot->setSendCallback(null);
        $this->app->onSubscribe($this->wampBot, $this->roomId);
    }

    public function onOpen(ConnectionInterface $conn) {
        $conn->botWelcomed = false;
        $conn->alone       = false;

        $this->app->onOpen($conn);
    }

    public function onSubscribe(ConnectionInterface $conn, $topic) {
        $this->app->onSubscribe($conn, $topic);

        if ((string)$topic == $this->roomId) {
            $this->genCount++;

            if (false === $conn->botWelcomed) {
                $conn->botWelcomed = true;

                $intro = (strstr($conn->Chat->name, 'Anonymous') ? 'Greetings' : "Hi {$conn->Chat->name}");
                $after = '';

                if (1 == $this->genCount) {
                    $after = " Looks like it's just you and I at the moment...I'll play copycat until someone else joins.";
                    $conn->alone = true;
                }

                $conn->event($topic, array(
                    'message'
                  , $this->wampBot->WAMP->sessionId
                  , "{$intro}! This is an IRC-like chatroom powered by Ratchet.{$after}"
                  , date('c')
                ));
            }
        }
    }

    public function onUnSubscribe(ConnectionInterface $conn, $topic) {
        $this->app->onUnSubscribe($conn, $topic);

        if ((string)$topic == $this->roomId) {
            $this->genCount--;
        }
    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude = array(), array $eligible = array()) {
        $this->app->onPublish($conn, $topic, $event, $exclude, $eligible);

        if ((string)$topic == $this->roomId) {
            if ($event == 'test') {
                 return $conn->event($topic, array('message', $this->wampBot->WAMP->sessionId, 'pass', date('c')));
            }

            if ($event == 'help' || $event == '!help') {
                return $conn->event($topic, array('message', $this->wampBot->WAMP->sessionId, 'No one can hear you scream in /dev/null', date('c')));
            }

            if ($conn->alone && 1 == $this->genCount) {
                return $conn->event($topic, array('message', $this->wampBot->WAMP->sessionId, $event, date('c')));
            }
        }
    }

    public function onCall(ConnectionInterface $conn, $id, $topic, array $params) {
        $this->app->onCall($conn, $id, $topic, $params);
    }

    public function onClose(ConnectionInterface $conn) {
        $this->app->onClose($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->app->onError($conn, $e);
    }
}