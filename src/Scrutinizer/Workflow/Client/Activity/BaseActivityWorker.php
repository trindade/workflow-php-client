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

namespace Scrutinizer\Workflow\Client\Activity;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Scrutinizer\ErrorReporter\NullReporter;
use Scrutinizer\ErrorReporter\ReporterInterface;
use Scrutinizer\RabbitMQ\Rpc\RpcClient;
use Scrutinizer\Workflow\Client\Exception\UnworkableStateException;
use Scrutinizer\Workflow\Client\Serializer\FlattenException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class BaseActivityWorker
{
    protected $channel;
    private $con;
    private $client;
    private $queueName;
    private $maxRuntime = 0;
    private $terminate;
    private $machineIdentifier;
    private $workerIdentifier;
    private $reporter;

    public function __construct(AMQPConnection $con, RpcClient $client, $queueName, $machineIdentifier = null, $workerIdentifier = null, ReporterInterface $reporter = null)
    {
        $this->con = $con;
        $this->channel = $con->channel();
        $this->client = $client;
        $this->queueName = $queueName;
        $this->machineIdentifier = $machineIdentifier ?: $this->determineMachine();
        $this->workerIdentifier = $workerIdentifier;
        $this->reporter = $reporter ?: new NullReporter();

        $this->channel->basic_qos(0, 1, false);

        $this->channel->queue_declare($queueName, false, true, false, false);
    }

    public function setMaxRuntime($seconds)
    {
        $this->maxRuntime = (integer) $seconds;
    }

    public function consume(AMQPMessage $message)
    {
        list($taskId, $executionId) = explode('.', $message->get('correlation_id'));

        $rs = $this->client->invoke('workflow_activity_start', array(
            'task_id' => $taskId,
            'execution_id' => $executionId,
            'machine_identifier' => $this->machineIdentifier,
            'worker_identifier' => $this->workerIdentifier,
        ), 'array');

        switch ($rs['action']) {
            case 'cancel':
                $this->channel->basic_ack($message->get('delivery_tag'));

                return;

            case 'start':
                break;

            default:
                throw new \LogicException(sprintf('Unknown action "%s".', $rs['action']));
        }

        try {
            $output = $this->handle($message->body);
            if ( ! is_string($output)) {
                throw new \RuntimeException(sprintf('The output must be a string, but got "%s".', gettype($output)));
            }

            $this->client->invoke('workflow_activity_result', array(
                'task_id' => $taskId,
                'execution_id' => $executionId,
                'status' => 'success',
                'result' => $output,
            ), 'array');
        } catch (\Exception $ex) {
            $this->reporter->reportException($ex);

            try {
                $this->client->invoke('workflow_activity_result', array(
                    'task_id' => $taskId,
                    'execution_id' => $executionId,
                    'status' => 'failure',
                    'failure_reason' => $ex->getMessage(),
                    'failure_exception' => FlattenException::create($ex),
                ), 'array');
            } catch (\Exception $failedEx) {
                $this->reporter->reportException($failedEx);

                // We re-send the failure report with a generic error message.
                try {
                    $this->client->invoke('workflow_activity_result', array(
                        'task_id' => $taskId,
                        'execution_id' => $executionId,
                        'status' => 'failure',
                        'failure_reason' => 'Original execution and automatic failure reporting failed; please check the workers logs.',
                    ), 'array');
                } catch (\Exception $nestedFailedEx) {
                    $this->reporter->reportException($nestedFailedEx);

                    // There is nothing we can do here, we just ignore it. The server will eventually garbage collect
                    // this task, and the appropriate coordinator will take action accordingly.
                }
            }

            if ($ex instanceof UnworkableStateException) {
                $this->terminate = true;
            }
        }

        $this->channel->basic_ack($message->get('delivery_tag'));
        $this->cleanUp();
    }

    /**
     * Produces an output for the given input.
     *
     * @param string $input
     *
     * @return string the result
     */
    abstract protected function handle($input);

    protected function initialize()
    {
    }

    protected function cleanUp()
    {
    }

    public function run()
    {
        $this->terminate = false;

        $startTime = time();
        $this->initialize();

        $this->channel->basic_consume($this->queueName, '', false, false, false, false, array($this, 'consume'));

        while (count($this->channel->callbacks) > 0 && false === $this->terminate) {
            if ($this->maxRuntime !== 0 && time() - $startTime > $this->maxRuntime) {
                return;
            }

            $this->channel->wait();
        }
    }

    private function determineMachine()
    {
        $proc = new Process('hostname');
        if (0 !== $proc->run()) {
            throw new ProcessFailedException($proc);
        }

        return trim($proc->getOutput());
    }
}