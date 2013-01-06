<?php

namespace Scrutinizer\Workflow\Client\Decider;

use JMS\Serializer\Serializer;
use PhpAmqpLib\Connection\AMQPConnection;
use Scrutinizer\RabbitMQ\Rpc\RpcClient;
use Scrutinizer\Workflow\Client\Transport\WorkflowExecution;

class CallbackDecider extends BaseDecider
{
    private $callback;

    public function __construct(AMQPConnection $con, Serializer $serializer, $queueName, RpcClient $client, CallbackInterface $callback)
    {
        parent::__construct($con, $serializer, $queueName, $client);
        $this->callback = $callback;
    }

    protected function consumeInternal(WorkflowExecution $execution, DecisionsBuilder $builder)
    {
        $this->callback->handle($execution, $builder);
    }

    protected function cleanUp()
    {
        $this->callback->cleanUp();
    }
}