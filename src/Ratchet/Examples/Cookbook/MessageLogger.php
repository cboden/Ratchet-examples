<?php
namespace Ratchet\Examples\Cookbook;
use Ratchet\Component\MessageComponentInterface;
use Ratchet\Resource\ConnectionInterface;
use Ratchet\Resource\Command\CommandInterface;
use Monolog\Logger;

class MessageLogger implements MessageComponentInterface {
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
            $this->_in->addInfo('New connection', array('num' => $this->_i, 'resource' => $conn->getID(), 'address' => $conn->getSocket()->getRemoteAddress()));
        }

        return $this->handleCommands($this->_component->onOpen($conn));
    }

    /**
     * @{inheritdoc}
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        if (null !== $this->_in) {
            $this->_in->addInfo('New message received', array('from' => $from->getID(), 'len' => strlen($msg), 'msg' => filter_var((string)$msg, FILTER_SANITIZE_SPECIAL_CHARS)));
        }

        return $this->handleCommands($this->_component->onMessage($from, $msg));
    }

    /**
     * @{inheritdoc}
     */
    public function onClose(ConnectionInterface $conn) {
        $this->_i--;

        if (null !== $this->_in) {
            $this->_in->addInfo('Connection closed', array('num' => $this->_i, 'resource' => $conn->getID()));
        }

        return $this->handleCommands($this->_component->onClose($conn));
    }

    /**
     * @{inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->_in->addError("({$e->getCode()}): {$e->getMessage()}", array('resource' => $conn->getID(), 'file' => $e->getFile(), 'line' => $e->getLine()));

        return $this->handleCommands($this->_component->onError($conn, $e));
    }

    /**
     * Handles command logging, help for all the onEvents
     * @param CommandInterface|null
     * @return CommandInterface|null
     * @internal
     * @todo Refactor to be self recursive
     */
    protected function handleCommands(CommandInterface $cmds = null) {
        if (null === $cmds || null === $this->_out) {
            return $cmds;
        }

        $count = 1;
        if ($cmds instanceof \Traversable) {
            $count = count($cmds);
            if ($count == 0) {
                return $cmds;
            }

            foreach ($cmds as $cmd) {
                $this->handleCommands($cmd);
            }
        } else {
            $ns    = get_class($cmds);
            $class = substr($ns, strrpos($ns, '\\') + 1);

            $context = array('command' => $class, 'on-resource' => $cmds->getConnection()->getID());

            if ($cmds instanceof SendMessage) {
                $context['payload'] = filter_var($cmds->getMessage(), FILTER_SANITIZE_SPECIAL_CHARS);
            }

            $this->_out->addDebug('Command queued for execution', $context);

            return $cmds;
        }

        $this->_out->addInfo('Number of commands queued for execution', array('num' => $count));

        return $cmds;
    }
}