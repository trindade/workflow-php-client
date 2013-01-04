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

use Scrutinizer\Workflow\Client\Transport\ActivityTask;

class DecisionsBuilder
{
    private $decisions = array();

    public function rescheduleActivity(ActivityTask $task, array $newControlData = array())
    {
        $this->scheduleActivity($task->activityName, $task->input, array_merge($task->control, $newControlData));
    }

    /**
     * @param string $activityName
     * @param string $input the encoded input data for the activity worker
     * @param array $controlData
     *
     * @return $this
     */
    public function scheduleActivity($activityName, $input, array $controlData = array())
    {
        $this->decisions[] = array(
            'type' => 'schedule_activity',
            'attributes' => array(
                'activity' => $activityName,
                'input' => $input,
                'control' => $controlData,
            ),
        );

        return $this;
    }

    public function failExecution($reason)
    {
        $this->decisions[] = array(
            'type' => 'execution_failed',
            'attributes' => array(
                'reason' => $reason,
            ),
        );

        return $this;
    }

    public function succeedExecution()
    {
        $this->decisions[] = array(
            'type' => 'execution_succeeded',
            'attributes' => array(),
        );

        return $this;
    }

    public function getDecisions()
    {
        return $this->decisions;
    }
}