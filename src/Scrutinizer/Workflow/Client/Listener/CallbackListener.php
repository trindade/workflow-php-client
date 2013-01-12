<?php

namespace Scrutinizer\Workflow\Client\Listener;

use JMS\Serializer\Serializer;
use PhpAmqpLib\Connection\AMQPConnection;
use Psr\Log\LoggerInterface;
use Scrutinizer\Workflow\Client\Transport\Event;

class CallbackListener extends AbstractEventListener
{
    private $callback;

    public function __construct(CallbackInterface $callback, AMQPConnection $con, $listenerQueue = null, Serializer $serializer = null, LoggerInterface $logger = null)
    {
        parent::__construct($con, $listenerQueue, $serializer, $logger);

        $this->callback = $callback;
        $this->listenForEvents($callback->getSubscribedEvents());
        $this->listenForWorkflows($callback->getSubscribedWorkflows());
    }

    protected function consumeInternal(Event $event)
    {
        try {
            $this->callback->handle($event);
            $this->callback->cleanUp();
        } catch (\Exception $ex) {
            $this->callback->cleanUp();

            throw $ex;
        }
    }

    public function run()
    {
        $this->callback->initialize();
        parent::run();
    }
}