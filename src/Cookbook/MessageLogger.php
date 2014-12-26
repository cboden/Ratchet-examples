<?php
namespace Ratchet\Cookbook;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\WsServerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Ratchet\AbstractConnectionDecorator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * A Ratchet component that wraps PSR\Log loggers tracking received and sent messages
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
    
    protected $_connections;

    public function __construct(MessageComponentInterface $component = null, LoggerInterface $incoming = null, LoggerInterface $outgoing = null) {
        $this->_component   = $component;
        $this->_connections = new \SplObjectStorage;
        
        if (null === $incoming) {
            $incoming = new NullLogger;
        }
        
        if (null === $outgoing) {
            $outgoing = new NullLogger;
        }
        
        $this->_in  = $incoming;
        $this->_out = $outgoing;
    }

    /**
     * {@inheritdoc}
     */
    function onOpen(ConnectionInterface $conn) {
        $this->_i++;

        $this->_in->info('onOpen', ['#open' => $this->_i, 'id' => $conn->resourceId, 'ip' => $conn->remoteAddress]);

        $decoratedConn = new MessageLoggedConnection($conn);
        $decoratedConn->setLogger($this->_out);
        
        $this->_connections->attach($conn, $decoratedConn);

        $this->_component->onOpen($decoratedConn);
    }

    /**
     * {@inheritdoc}
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        $this->_in->info('onMsg', ['from' => $from->resourceId, 'len' => strlen($msg), 'msg' => $msg]);

        $this->_component->onMessage($this->_connections[$from], $msg);
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $conn) {
        $this->_i--;

        $this->_in->info('onClose', ['#open' => $this->_i, 'id' => $conn->resourceId]);
        
        $decorated = $this->_connections[$conn];
        $this->_connections->detach($conn);

        $this->_component->onClose($decorated);
    }

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->_in->error("onError: ({$e->getCode()}): {$e->getMessage()}", ['id' => $conn->resourceId, 'file' => $e->getFile(), 'line' => $e->getLine()]);

        $this->_component->onError($this->_connections[$conn], $e);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubProtocols() {
        if ($this->_component instanceof WsServerInterface) {
            return $this->_component->getSubProtocols();
        } else {
            return [];
        }
    }
}

class MessageLoggedConnection extends AbstractConnectionDecorator implements ConnectionInterface, LoggerAwareInterface {
    use LoggerAwareTrait;
    
    public function send($data) {
        $this->logger->info('send', ['to' => $this->resourceId, 'len' => strlen($data), 'msg' => $data]);
        
        $this->getConnection()->send($data);
        
        return $this;
    }
    
    public function close($code = null) {
        $this->getConnection()->close($code);
    }
}