<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Scrutinizer\Workflow\Client\Decider;

use JMS\Serializer\Serializer;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Scrutinizer\RabbitMQ\Rpc\RpcClient;
use Scrutinizer\RabbitMQ\Rpc\RpcError;
use Scrutinizer\Workflow\Client\Transport\WorkflowExecution;
use Scrutinizer\Workflow\RabbitMq\Transport\Decision;

abstract class BaseDecider
{
    protected $client;
    private $con;
    private $channel;
    private $serializer;

    public function __construct(AMQPConnection $con, Serializer $serializer, $queueName, RpcClient $client)
    {
        $this->con = $con;
        $this->channel = $con->channel();
        $this->serializer = $serializer;
        $this->client = $client;

        $this->channel->queue_declare('workflow_decision', false, true, false, false);

        $this->channel->queue_declare($queueName, false, true, false, false);
        $this->channel->basic_consume($queueName, '', false, false, false, false, array($this, 'consume'));
    }

    public function consume(AMQPMessage $message)
    {
        /** @var $execution WorkflowExecution */
        $execution = $this->serializer->deserialize($message->body, 'Scrutinizer\Workflow\Client\Transport\WorkflowExecution', 'json');

        $decisionBuilder = new DecisionsBuilder();

        $this->consumeInternal($execution, $decisionBuilder);

        $rs = $this->client->invoke('workflow_decision', array(
            'execution_id' => $execution->id,
            'decisions' => $decisionBuilder->getDecisions(),
        ), 'array');

        if ($rs instanceof RpcError) {
            $this->client->invoke('workflow_decision', array(
                'execution_id' => $execution->id,
                'decisions' => (new DecisionsBuilder())->failExecution($rs->message)->getDecisions(),
            ), 'array');
        }

        $this->channel->basic_ack($message->get('delivery_tag'));
    }

    /**
     * Makes coordination decisions.
     *
     * Decisions can include scheduling new activity tasks, and failing or succeeding the execution.
     *
     * @param \Scrutinizer\Workflow\Client\Transport\WorkflowExecution $execution
     * @param DecisionsBuilder $decisionsBuilder
     *
     * @return void
     */
    abstract protected function consumeInternal(WorkflowExecution $execution, DecisionsBuilder $decisionsBuilder);

    public function run()
    {
        while (count($this->channel->callbacks) > 0) {
            $this->channel->wait();
        }
    }
}