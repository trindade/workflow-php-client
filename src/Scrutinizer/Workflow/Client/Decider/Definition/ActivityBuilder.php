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

class ActivityBuilder
{
    private $parent;
    private $input;
    private $id;
    private $maxRetries;
    private $retryDeciderCallback;

    public function __construct(FlowBuilder $parent)
    {
        $this->parent = $parent;
    }

    public function id($id)
    {
        $this->id = $id;

        return $this;
    }

    public function useWorkflowInput()
    {
        $this->input = new WorkflowInput();

        return $this;
    }

    public function useActivityResult($activityId)
    {
        $this->input = new ActivityResultInput($activityId);

        return $this;
    }

    public function prepareInput(\Closure $callback)
    {
        $this->input = $callback;

        return $this;
    }

    public function maxRetries($tries)
    {
        $this->maxRetries = (integer) $tries;

        return $this;
    }

    public function retryIf(\Closure $callback)
    {
        $this->retryDeciderCallback = $callback;

        return $this;
    }

    public function end()
    {
        return $this->parent;
    }
}

class ActivityResultInput
{
    public $activityId;

    public function __construct($activityId)
    {
        $this->activityId = $activityId;
    }
}
class WorkflowInput { }