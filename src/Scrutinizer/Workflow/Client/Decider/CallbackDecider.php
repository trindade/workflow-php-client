<?php

namespace Scrutinizer\Workflow\Client\Decider;

use JMS\Serializer\Serializer;
use PhpAmqpLib\Connection\AMQPConnection;
use Scrutinizer\RabbitMQ\Rpc\RpcClient;
use Scrutinizer\Workflow\Client\Worker\ChannelAwareInterface;
use Scrutinizer\Workflow\Client\Transport\WorkflowExecution;

class CallbackDecider extends BaseDecider
{
    private $callback;

    public function __construct(AMQPConnection $con, Serializer $serializer, RpcClient $client, $queueName, CallbackInterface $callback)
    {
        parent::__construct($con, $serializer, $queueName, $client);
        $this->callback = $callback;
    }

    protected function consumeInternal(WorkflowExecution $execution, DecisionsBuilder $builder)
    {
        if ($this->callback instanceof ChannelAwareInterface) {
            $this->callback->setChannel($this->channel);
        }

        $this->callback->handle($execution, $builder);
    }

    protected function cleanUp()
    {
        $this->callback->cleanUp();
    }
}