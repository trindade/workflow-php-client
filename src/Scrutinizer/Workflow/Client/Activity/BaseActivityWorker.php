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
use Scrutinizer\RabbitMQ\Rpc\RpcClient;

abstract class BaseActivityWorker
{
    private $con;
    private $channel;
    private $client;
    private $queueName;

    public function __construct(AMQPConnection $con, RpcClient $client, $queueName)
    {
        $this->con = $con;
        $this->channel = $con->channel();
        $this->client = $client;
        $this->queueName = $queueName;

        $this->channel->queue_declare($queueName, false, true, false, false);
        $this->channel->basic_consume($queueName, '', false, false, false, false, array($this, 'consume'));
    }

    public function consume(AMQPMessage $message)
    {
        try {
            $output = $this->handle($message->body);
            if ( ! is_string($output)) {
                throw new \RuntimeException(sprintf('The output must be a string, but got "%s".', gettype($output)));
            }

            $rs = $this->client->invoke('workflow_activity_result', array(
                'task_id' => $message->get('correlation_id'),
                'status' => 'success',
                'result' => $output,
            ), 'array');

            // TODO: Handle error?
        } catch (\Exception $ex) {
            $rs = $this->client->invoke('workflow_activity_result', array(
                'task_id' => $message->get('correlation_id'),
                'status' => 'failure',
                'failure_reason' => $ex->getMessage(),
            ), 'array');

            // TODO: Handle error?
        }

        $this->channel->basic_ack($message->get('delivery_tag'));
    }

    /**
     * Produces an output for the given input.
     *
     * @param string $input
     *
     * @return string the result
     */
    abstract protected function handle($input);

    public function run()
    {
        while (count($this->channel->callbacks) > 0) {
            $this->channel->wait();
        }
    }
}