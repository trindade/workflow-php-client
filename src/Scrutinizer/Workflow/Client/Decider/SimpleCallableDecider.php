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
use Scrutinizer\RabbitMQ\Rpc\RpcClient;
use Scrutinizer\Workflow\Client\Transport\WorkflowExecution;

class SimpleCallableDecider extends BaseDecider
{
    private $callback;

    public function __construct(AMQPConnection $con, Serializer $serializer, $queueName, RpcClient $client, callable $callback)
    {
        parent::__construct($con, $serializer, $queueName, $client);
        $this->callback= $callback;
    }

    protected function consumeInternal(WorkflowExecution $execution, DecisionsBuilder $decisionsBuilder)
    {
        call_user_func($this->callback, $execution, $decisionsBuilder);
    }
}