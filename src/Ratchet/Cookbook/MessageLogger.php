<?php
namespace Ratchet\Cookbook;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\WsServerInterface;
use Monolog\Logger;

class MessageLogger implements MessageComponentInterface, WsServerInterface {
    /**
     * @var Monolog\Logger|null
     */
    protected $_in;

    /**
     * @var Monolog\Logger|null
     */
    protected $_out;

    /**
     * @var Ratchet\Component\MessageComponentInterface|null
     */
    protected $_component;

    /**
     * Counts the number of open connections
     * @var int
     */
    protected $_i = 0;

    public function __construct(MessageComponentInterface $component = null, Logger $incoming = null, Logger $outgoing = null) {
        $this->_component = $component;
        $this->_in        = $incoming;
        $this->_out       = $outgoing;
    }

    /**
     * {@inheritdoc}
     */
    function onOpen(ConnectionInterface $conn) {
        $this->_i++;

        if (null !== $this->_in) {
            $this->_in->addInfo('New connection', array('num' => $this->_i, 'resource' => $conn->resourceId, 'address' => $conn->remoteAddress));
        }

        $this->_component->onOpen($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        if (null !== $this->_in) {
            $this->_in->addInfo('New message received', array('from' => $from->resourceId, 'len' => strlen($msg), 'msg' => filter_var((string)$msg, FILTER_SANITIZE_SPECIAL_CHARS)));
        }

        $this->_component->onMessage($from, $msg);
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $conn) {
        $this->_i--;

        if (null !== $this->_in) {
            $this->_in->addInfo('Connection closed', array('num' => $this->_i, 'resource' => $conn->resourceId));
        }

        $this->_component->onClose($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->_in->addError("({$e->getCode()}): {$e->getMessage()}", array('resource' => $conn->resourceId, 'file' => $e->getFile(), 'line' => $e->getLine()));

        $this->_component->onError($conn, $e);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubProtocols() {
        if ($this->_component instanceof WsServerInterface) {
            return $this->_component->getSubProtocols();
        } else {
            return array();
        }
    }
}