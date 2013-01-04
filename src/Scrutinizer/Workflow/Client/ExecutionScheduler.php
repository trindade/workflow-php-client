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

namespace Scrutinizer\Workflow\Client;

use PhpAmqpLib\Connection\AMQPConnection;
use Scrutinizer\RabbitMQ\Rpc\RpcClient;

class ExecutionScheduler
{
    private $client;

    public function __construct(RpcClient $client)
    {
        $this->client = $client;
    }

    /**
     * Schedules the execution of a command.
     *
     * @param string $workflowName The name of the workflow which was registered with the workflow server.
     * @param string $input The input may be any string that is understood by the decider associated with this workflow.
     * @param string[] $tags Some tags which should be applied to this execution.
     *
     * @return array An array of the form ["execution_id" => "123"]
     */
    public function startExecution($workflowName, $input, array $tags = array())
    {
        return $this->client->invoke('workflow_execution', array(
            'workflow' => $workflowName,
            'input' => $input,
            'tags' => $tags,
        ), 'array');
    }
}