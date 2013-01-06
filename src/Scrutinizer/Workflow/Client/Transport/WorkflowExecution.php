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
    /** @Serializer\Type("integer") */
    public $id;

    /** @Serializer\Type("string") */
    public $input;

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

            if ($event->task instanceof ActivityTask && ! $event->task->isOpen()) {
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
            return new Some($this->tasks[$i]);
        }

        return None::create();
    }

    public function findSuccessfulActivity($activityName)
    {
        return $this->tasks->find(function(AbstractTask $task) use ($activityName) {
            return $task instanceof ActivityTask && $task->hasSucceeded() && $task->activityName === $activityName;
        });
    }

    public function hasOpenActivities()
    {
        return -1 !== $this->tasks->lastIndexWhere(function(AbstractTask $task) {
            return $task instanceof ActivityTask && $task->isOpen();
        });
    }

    /** @Serializer\PostDeserialize */
    private function postDeserialize()
    {
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
        $task->next = None::create();

        foreach ($this->history as $event) {
            /** @var $event Event */

            if (isset($event->attributes['task_id'])) {
                $event->task = $tasks[$event->attributes['task_id']];
            } else if (isset($event->attributes['decision_task_id'])) {
                $event->task = $tasks[$event->attributes['decision_task_id']];
            } else if (isset($event->attributes['activity_task_id'])) {
                $event->task = $tasks[$event->attributes['activity_task_id']];
            }
        }
    }
}