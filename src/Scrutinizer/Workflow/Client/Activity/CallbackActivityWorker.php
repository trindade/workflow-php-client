<?php

namespace Scrutinizer\Workflow\Client\Activity;

use JMS\Serializer\Serializer;
use PhpAmqpLib\Connection\AMQPConnection;
use Scrutinizer\RabbitMQ\Rpc\RpcClient;

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
        $rs = $this->callback->handle($input);
        $this->callback->cleanUp();

        return $rs;
    }
}