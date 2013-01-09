<?php

namespace Scrutinizer\Workflow\Client\Logger;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\AbstractLogger;

class TaskLogger extends AbstractLogger
{
    private $channel;
    private $executionId;
    private $taskId;

    public function __construct(AMQPConnection $con, $executionId, $taskId)
    {
        $this->channel = $con->channel();
        $this->executionId = $executionId;
        $this->taskId = $taskId;

        $this->channel->exchange_declare('workflow_log', 'topic');
    }

    public function log($level, $message, array $context = array())
    {
        $this->channel->basic_publish(
            new AMQPMessage(json_encode(array(
                'message'
            ))),
            'workflow_log',
            ''
        );
    }
}