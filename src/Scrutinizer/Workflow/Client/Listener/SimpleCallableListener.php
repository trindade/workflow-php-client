<?php

namespace Scrutinizer\Workflow\Client\Listener;

use JMS\Serializer\Serializer;
use PhpAmqpLib\Connection\AMQPConnection;
use Psr\Log\LoggerInterface;
use Scrutinizer\Workflow\Client\Transport\Event;

class SimpleCallableListener extends AbstractEventListener
{
    private $handler;

    /**
     * @param \PhpAmqpLib\Connection\AMQPConnection $con
     * @param callable   $handler       The handler
     * @param string     $listenerQueue If set, messages are durably routed to this queue until acknowledged.
     *                                  If not set, messages are routed to an exclusive, non-durable queue.
     * @param Serializer $serializer    The serializer
     * @param Logger     $logger        The logger
     */
    public function __construct(AMQPConnection $con, callable $handler, $listenerQueue = null, Serializer $serializer = null, LoggerInterface $logger = null)
    {
        parent::__construct($con, $listenerQueue, $serializer, $logger);
        $this->handler = $handler;
    }

    protected function consumeInternal(Event $event)
    {
        call_user_func($this->handler, $event);
    }
}