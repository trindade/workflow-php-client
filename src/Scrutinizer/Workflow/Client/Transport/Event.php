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

class Event
{
    /** @Serializer\Type("string") */
    public $id;

    /** @Serializer\Type("DateTime") */
    public $createdAt;

    /** @Serializer\Type("string") */
    public $name;

    /** @Serializer\Type("array") */
    public $attributes;

    /**
     * @Serializer\Type("Scrutinizer\Workflow\Client\Transport\WorkflowExecution")
     * @var WorkflowExecution
     */
    public $workflowExecution;

    /**
     * This is populated by the WorkflowExecution after deserialization.
     *
     * @var AbstractTask
     */
    public $task;
}