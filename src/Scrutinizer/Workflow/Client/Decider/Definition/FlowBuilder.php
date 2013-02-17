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

namespace Scrutinizer\Workflow\Client\Decider\Definition;

use Scrutinizer\Workflow\Client\Decider\DecisionsBuilder;
use Scrutinizer\Workflow\Client\Transport\WorkflowExecution;

abstract class FlowBuilder
{
    protected $units = array();
    private $parent;

    public static function create()
    {
        return new static();
    }

    public function __construct(FlowBuilder $parent = null)
    {
        $this->parent = $parent;
    }

    public function activity($activityName)
    {
        return $this->units[] = new ActivityBuilder($this);
    }

    public function end()
    {
        return $this->parent;
    }

    public function buildCallback()
    {
        return function (WorkflowExecution $execution, DecisionsBuilder $builder) {
            foreach ($this->units as $unit) {

            }
        };
    }
}