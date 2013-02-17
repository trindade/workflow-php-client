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
    private $workflowNames = array();
    private $eventNames = array();
    private $maxRuntime = 0;

    /**
     * @param \PhpAmqpLib\Connection\AMQPConnection $con
     * @param string     $listenerQueue If set, messages are durably routed to this queue until acknowledged.
     *                                  If not set, messages are routed to an exclusive, non-durable queue.
     * @param Serializer $serializer    The serializer
     * @param Logger     $logger        The logger
     */
    public function __construct(AMQPConnection $con, $listenerQueue = null, Serializer $serializer = null, LoggerInterface $logger = null)
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

        // Setting a lower pre-fetch count only makes sense for non-exclusive queues.
        if (null !== $listenerQueue) {
            $this->channel->basic_qos(0, 1, false);
        }

        $this->channel->exchange_declare('workflow_events', 'topic');

        list($queueName, ) = $this->channel->queue_declare($listenerQueue ?: '', false, null !== $listenerQueue, null === $listenerQueue, null === $listenerQueue);
        $this->channel->queue_bind($queueName, 'workflow_events', '#');
        $this->channel->basic_consume($queueName, '', false, false, false, false, array($this, 'consume'));
    }

    public function setMaxRuntime($seconds)
    {
        $this->maxRuntime = (integer) $seconds;
    }

    public function listenForWorkflows(array $names)
    {
        $this->workflowNames = $names;
    }

    public function listenForEvents(array $names)
    {
        $this->eventNames = $names;
    }

    public function consume(AMQPMessage $message)
    {
        try {
            /** @var $event Event */
            $event = $this->deserialize($message->body, 'Scrutinizer\Workflow\Client\Transport\Event');

            if ((empty($this->workflowNames) || in_array($event->workflowExecution->workflowName, $this->workflowNames, true))
                    && (empty($this->eventNames) || in_array($event->name, $this->eventNames, true))) {
                $this->consumeInternal($event);
            }

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
        $startTime = time();
        while (count($this->channel->callbacks) > 0) {
            if ($this->maxRuntime !== 0 && time() - $startTime > $this->maxRuntime) {
                return;
            }

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