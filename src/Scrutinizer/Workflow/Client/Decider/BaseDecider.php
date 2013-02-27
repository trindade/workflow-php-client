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

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Exclusion\GroupsExclusionStrategy;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Scrutinizer\ErrorReporter\NullReporter;
use Scrutinizer\ErrorReporter\ReporterInterface;
use Scrutinizer\RabbitMQ\Rpc\RpcClient;
use Scrutinizer\RabbitMQ\Rpc\RpcError;
use Scrutinizer\RabbitMQ\Rpc\RpcErrorException;
use Scrutinizer\Workflow\Client\Serializer\FlattenException;
use Scrutinizer\Workflow\Client\Transport\WorkflowExecution;
use Scrutinizer\Workflow\RabbitMq\Transport\Decision;

abstract class BaseDecider
{
    protected $channel;
    private $serializer;
    private $client;
    private $con;
    private $maxRuntime = 0;
    private $reporter;

    public function __construct(AMQPConnection $con, Serializer $serializer, $queueName, RpcClient $client, ReporterInterface $reporter = null)
    {
        $this->con = $con;
        $this->channel = $con->channel();
        $this->serializer = $serializer;
        $this->client = $client;
        $this->reporter = $reporter ?: new NullReporter();

        $this->channel->basic_qos(0, 1, false);

        $this->channel->queue_declare('workflow_decision', false, true, false, false);

        $this->channel->queue_declare($queueName, false, true, false, false);
        $this->channel->basic_consume($queueName, '', false, false, false, false, array($this, 'consume'));
    }

    public function setMaxRuntime($seconds)
    {
        $this->maxRuntime = (integer) $seconds;
    }

    public function consume(AMQPMessage $message)
    {
        /** @var $execution WorkflowExecution */
        $execution = $this->deserialize($message->body, 'Scrutinizer\Workflow\Client\Transport\WorkflowExecution');

        $decisionBuilder = new DecisionsBuilder();

        try {
            $this->consumeInternal($execution, $decisionBuilder);
            $this->cleanUp();
        } catch (\Exception $ex) {
            $this->reporter->reportException($ex);

            // If we reach this, there is some sort of logical error in the program. We will not be able to recover from
            // this state automatically, and in order to not block all other messages, we will simply acknowledge this
            // one. This will eventually result in the execution being garbage collected. We will try to clean-up as
            // much as possible, and then throw the original exception as this was the root cause for this problem.

            try {
                $this->cleanUp();
            } catch (\Exception $nestedEx) {
                $this->reporter->reportException($nestedEx);
            }

            try {
                $this->client->invoke('workflow_decision', array(
                    'execution_id' => $execution->id,
                    'decisions' => (new DecisionsBuilder())
                        ->failExecution($ex->getMessage(), array(
                            'exception' => FlattenException::create($ex),
                        ))
                        ->getDecisions(),
                ), 'array');
            } catch (\Exception $nestedEx) {
                $this->reporter->reportException($nestedEx);
            }

            try {
                $this->channel->basic_ack($message->get('delivery_tag'));
            } catch (\Exception $ex) {
                $this->reporter->reportException($ex);
            }

            throw $ex;
        }

        try {
            $this->client->invoke('workflow_decision', array(
                'execution_id' => $execution->id,
                'decisions' => $decisionBuilder->getDecisions(),
            ), 'array');
        } catch (RpcErrorException $ex) {
            $this->reporter->reportException($ex);

            try {
                $this->client->invoke('workflow_decision', array(
                    'execution_id' => $execution->id,
                    'decisions' => (new DecisionsBuilder())->failExecution($ex->getMessage())->getDecisions(),
                ), 'array');
            } catch (\Exception $ex) {
                $this->reporter->reportException($ex);

                // If another error occurs, there is nothing we can do but to discard the message, and let the server
                // garbage collect the execution.
            }
        }

        $this->channel->basic_ack($message->get('delivery_tag'));
    }

    /**
     * Sub-classes may want to override this method to perform common clean-up tasks after consumption of a message.
     */
    protected function cleanUp()
    {
    }

    protected function serialize($data, array $groups = array())
    {
        $context = new SerializationContext();
        if ( ! empty($groups)) {
            $context->setGroups($groups);
        }

        return $this->serializer->serialize($data, 'json', $context);
    }

    protected function deserialize($data, $type, array $groups = array())
    {
        $context = new DeserializationContext();
        if ( ! empty($groups)) {
            $context->setGroups($groups);
        }

        return $this->serializer->deserialize($data, $type, 'json', $context);
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
        $startTime = time();
        while (count($this->channel->callbacks) > 0) {
            if ($this->maxRuntime !== 0 && time() - $startTime > $this->maxRuntime) {
                return;
            }

            $this->channel->wait();
        }
    }
}