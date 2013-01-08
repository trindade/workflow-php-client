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

namespace Scrutinizer\Workflow\Client\Serializer;

use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;

class TaskHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return array(array(
            'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
            'type' => 'Task',
            'format' => 'json',
        ));
    }

    public function deserializeTaskFromJson(JsonDeserializationVisitor $visitor, array $data, array $type)
    {
        switch ($data['type']) {
            case 'activity':
                $class = 'Scrutinizer\Workflow\Client\Transport\ActivityTask';
                break;

            case 'decision':
                $class = 'Scrutinizer\Workflow\Client\Transport\DecisionTask';
                break;

            case 'workflow_execution':
                $class = 'Scrutinizer\Workflow\Client\Transport\WorkflowExecutionTask';
                break;

            default:
                throw new \LogicException(sprintf('Unsupported decision type "%s".', $data['type']));
        }

        return $visitor->getNavigator()->accept($data, array('name' => $class, 'params' => array()), $visitor);
    }
}