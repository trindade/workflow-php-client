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

    public function __construct(AMQPConnection $con, RpcClient $client, $queueName, CallbackInterface $callback)
    {
        parent::__construct($con, $client, $queueName);
        $this->callback = $callback;
    }

    protected function handle($input)
    {
        if ($this->callback instanceof ChannelAwareInterface) {
            $this->callback->setChannel($this->channel);
        }

        try {
            $rs = $this->callback->handle($input);
        } catch (\Exception $ex) {
            $this->callback->cleanUp();

            throw $ex;
        }

        $this->callback->cleanUp();

        return $rs;
    }

    protected function initialize()
    {
        $this->callback->initialize();
    }
}