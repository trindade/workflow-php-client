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

namespace Scrutinizer\Workflow\Client\Transport;

use JMS\Serializer\Annotation as Serializer;
use PhpCollection\Sequence;
use PhpOption\None;
use PhpOption\Option;
use PhpOption\Some;

class WorkflowExecution
{
    const STATE_OPEN = 'open';
    const STATE_FAILED = 'failed';
    const STATE_SUCCEEDED = 'succeeded';
    const STATE_CANCELED = 'canceled';
    const STATE_TIMED_OUT = 'timed_out';

    /** @Serializer\Type("string") */
    public $id;

    /** @Serializer\Type("string") */
    public $workflowName;

    /** @Serializer\Type("string") */
    public $input;

    /** @Serializer\Type("string") */
    public $state;

    /**
     * @Serializer\Type("PhpCollection\Sequence<Task>")
     *
     * @var Sequence
     */
    public $tasks;

    /**
     * @Serializer\Type("PhpCollection\Sequence<Scrutinizer\Workflow\Client\Transport\Event>")
     *
     * @var Sequence
     */
    public $history;

    public function isInitialDecision()
    {
        return 1 === count($this->tasks);
    }

    public function isOpen()
    {
        return self::STATE_OPEN === $this->state;
    }

    public function hasSucceeded()
    {
        return self::STATE_SUCCEEDED === $this->state;
    }

    public function hasFailed()
    {
        return self::STATE_FAILED === $this->state;
    }

    public function hasTimedOut()
    {
        return self::STATE_TIMED_OUT === $this->state;
    }

    public function isCanceled()
    {
        return self::STATE_CANCELED === $this->state;
    }

    /**
     * @return AbstractActivityTask[]
     */
    public function getClosedActivityTasksSinceLastDecision()
    {
        $lastEventId = $this->history->last()->get()->id;
        $i = 1 + $this->history->lastIndexWhere(function(Event $event) use ($lastEventId) {
            return $event->id !== $lastEventId && $event->name === 'execution.new_decision_task';
        });

        $tasks = array();
        for ($c=count($this->history); $i<$c; $i++) {
            /** @var $event Event */
            $event = $this->history->get($i);

            if ($event->task instanceof AbstractActivityTask && ! $event->task->isOpen() && ! in_array($event->task, $tasks, true)) {
                $tasks[] = $event->task;
            }
        }

        return $tasks;
    }

    /**
     * Returns an option for the last task satisfying the given predicate.
     *
     * @param callable $predicate
     *
     * @return Option<AbstractTask>
     */
    public function getLastTaskMatching(\Closure $predicate)
    {
        if (-1 !== $i = $this->tasks->lastIndexWhere($predicate)) {
            return new Some($this->tasks->get($i));
        }

        return None::create();
    }

    public function findSuccessfulActivity($activityName)
    {
        return $this->tasks->find(function(AbstractTask $task) use ($activityName) {
            return $task instanceof AbstractActivityTask && $task->hasSucceeded() && $task->getName() === $activityName;
        });
    }

    public function hasOpenActivities()
    {
        return -1 !== $this->tasks->lastIndexWhere(function(AbstractTask $task) {
            return $task instanceof AbstractActivityTask && $task->isOpen();
        });
    }

    /** @Serializer\PostDeserialize */
    private function postDeserialize()
    {
        if (null === $this->tasks) {
            $this->tasks = new Sequence();
        }
        if (null === $this->history) {
            $this->history = new Sequence();
        }

        $tasks = array();
        $previous = null;
        foreach ($this->tasks as $task) {
            /** @var $task AbstractTask */

            $tasks[$task->id] = $task;
            if (null === $previous) {
                $task->previous = None::create();
            } else {
                $task->previous = new Some($previous);
                $previous->next = new Some($task);
            }

            $previous = $task;
        }
        if (isset($task)) {
            $task->next = None::create();
        }

        foreach ($this->history as $event) {
            /** @var $event Event */

            if (isset($event->attributes['task_id'])) {
                $event->task = $tasks[$event->attributes['task_id']];
            } elseif (isset($event->attributes['decision_task_id'])) {
                $event->task = $tasks[$event->attributes['decision_task_id']];
            } elseif (isset($event->attributes['activity_task_id'])) {
                $event->task = $tasks[$event->attributes['activity_task_id']];
            }
        }
    }
}