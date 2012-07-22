<?php
namespace Ratchet\Cookbook;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\WsServerInterface;
use Monolog\Logger;

/**
 * A Ratchet component that wraps Monolog loggers tracking received messages
 * @todo Get outgoing working; could create LoggingConnection decorator
 */
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
            $this->_in->addInfo('onOpen', array('#open' => $this->_i, 'id' => $conn->resourceId, 'ip' => $conn->remoteAddress));
        }

        $this->_component->onOpen($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        if (null !== $this->_in) {
            $this->_in->addInfo('onMsg', array('from' => $from->resourceId, 'len' => strlen($msg), 'msg' => $msg));
        }

        $this->_component->onMessage($from, $msg);
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $conn) {
        $this->_i--;

        if (null !== $this->_in) {
            $this->_in->addInfo('onClose', array('#open' => $this->_i, 'id' => $conn->resourceId));
        }

        $this->_component->onClose($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->_in->addError("onError: ({$e->getCode()}): {$e->getMessage()}", array('id' => $conn->resourceId, 'file' => $e->getFile(), 'line' => $e->getLine()));

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