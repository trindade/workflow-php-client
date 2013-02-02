<?php

namespace Scrutinizer\Workflow\Client\Activity;

use JMS\Serializer\Serializer;
use PhpAmqpLib\Connection\AMQPConnection;
use Psr\Log\LoggerAwareInterface;
use Scrutinizer\RabbitMQ\Rpc\RpcClient;
use Scrutinizer\Workflow\Client\Worker\ChannelAwareInterface;

class CallbackActivityWorker extends BaseActivityWorker
{
    private $callback;

    public function __construct(AMQPConnection $con, RpcClient $client, $queueName, CallbackInterface $callback, $machineIdentifier, $workerIdentifier)
    {
        parent::__construct($con, $client, $queueName, $machineIdentifier, $workerIdentifier);
        $this->callback = $callback;
    }

    protected function handle($input)
    {
        if ($this->callback instanceof ChannelAwareInterface) {
            $this->callback->setChannel($this->channel);
        }

        return $this->callback->handle($input);
    }

    protected function initialize()
    {
        $this->callback->initialize();
    }

    protected function cleanUp()
    {
        $this->callback->cleanUp();
    }
}