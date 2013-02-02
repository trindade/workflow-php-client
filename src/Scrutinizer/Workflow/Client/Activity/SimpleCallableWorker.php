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
use Scrutinizer\RabbitMQ\Rpc\RpcClient;

class SimpleCallableWorker extends BaseActivityWorker
{
    private $callback;

    public function __construct(AMQPConnection $con, RpcClient $client, $queueName, callable $callback, $machineIdentifier = null, $workerIdentifier = null)
    {
        parent::__construct($con, $client, $queueName, $machineIdentifier, $workerIdentifier);
        $this->callback = $callback;
    }

    protected function handle($input)
    {
        return call_user_func($this->callback, $input);
    }
}