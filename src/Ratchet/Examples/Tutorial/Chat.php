<?php
namespace Ratchet\Examples\Tutorial;
use Ratchet\Component\MessageComponentInterface;
use Ratchet\Resource\ConnectionInterface;
use Ratchet\Resource\Command\Action\SendMessage;
use Ratchet\Resource\Command\Action\CloseConnection;
use Ratchet\Resource\Command\Composite as CommandComposite;

class Chat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // This is a collection of commands to send back to the caller
        $commands = new CommandComposite;

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // The sender is not the receiver, enqueue a message to send to each client connected
                $messageCommand = new SendMessage($client);
                $messageCommand->setMessage($msg);

                $commands->enqueue($messageCommand);
            }
        }

        // Return our collection of SendMessage commands to execute
        return $commands;
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        return new CloseConnection($conn);
    }
}