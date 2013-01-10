<?php

namespace Scrutinizer\Workflow\Client\Listener;

use JMS\Serializer\Exclusion\GroupsExclusionStrategy;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Scrutinizer\Workflow\Client\Serializer\TaskHandler;
use Scrutinizer\Workflow\Client\Transport\Event;

abstract class AbstractEventListener
{
    private $con;
    private $channel;
    private $hasStaticQueue;
    private $serializer;
    private $logger;

    /**
     * @param \PhpAmqpLib\Connection\AMQPConnection $con
     * @param string $pattern "*" substitues exactly one word, "#" zero or more words
     * @param string $listenerQueue If set, messages are durably routed to this queue until acknowledged.
     *                              If not set, messages are routed to an exclusive, non-durable, auto-ack queue.
     */
    public function __construct(AMQPConnection $con, $pattern, $listenerQueue = null, Serializer $serializer = null, LoggerInterface $logger = null)
    {
        $this->con = $con;
        $this->channel = $con->channel();
        $this->hasStaticQueue = $listenerQueue !== null;
        $this->serializer = $serializer ?: SerializerBuilder::create()
            ->addDefaultHandlers()
            ->configureHandlers(function(HandlerRegistry $registry) {
                $registry->registerSubscribingHandler(new TaskHandler());
            })
            ->build();
        $this->logger = $logger ?: new NullLogger();

        $this->channel->exchange_declare('workflow_events', 'topic');

        list($queueName, ) = $this->channel->queue_declare($listenerQueue ?: '', false, null !== $listenerQueue, null === $listenerQueue, null === $listenerQueue);
        $this->channel->queue_bind($queueName, 'workflow_events', $pattern);
        $this->channel->basic_consume($queueName, '', false, false, false, false, array($this, 'consume'));
    }

    public function consume(AMQPMessage $message)
    {
        try {
            $event = $this->deserialize($message->body, 'Scrutinizer\Workflow\Client\Transport\Event');
            $this->consumeInternal($event);

            if ($this->hasStaticQueue) {
                $this->channel->basic_ack($message->get('delivery_tag'));
            }
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage(), array('exception' => $ex));

            if ($this->hasStaticQueue) {
                $this->channel->basic_nack($message->get('delivery_tag'));
            }
        }
    }

    public function run()
    {
        while (count($this->channel->callbacks) > 0) {
            $this->channel->wait();
        }
    }

    protected function serialize($data, array $groups = array())
    {
        $this->serializer->setExclusionStrategy(empty($groups) ? null : new GroupsExclusionStrategy($groups));

        return $this->serializer->serialize($data, 'json');
    }

    protected function deserialize($data, $type, array $groups = array())
    {
        $this->serializer->setExclusionStrategy(empty($groups) ? null : new GroupsExclusionStrategy($groups));

        return $this->serializer->deserialize($data, $type, 'json');
    }

    abstract protected function consumeInternal(Event $event);
}