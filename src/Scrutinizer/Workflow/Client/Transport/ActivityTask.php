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

class ActivityTask extends AbstractActivityTask
{
    const STATE_OPEN = 'open';
    const STATE_FAILED = 'failed';
    const STATE_SUCCEEDED = 'succeeded';

    /** @Serializer\Type("string") */
    public $input;

    /** @Serializer\Type("string") */
    public $result;

    /** @Serializer\Type("string") */
    public $state;

    /** @Serializer\Type("string") */
    public $failureReason;

    /** @Serializer\Type("string") */
    public $activityName;

    public function isOpen()
    {
        return self::STATE_OPEN === $this->state;
    }

    public function hasFailed()
    {
        return self::STATE_FAILED === $this->state;
    }

    public function hasSucceeded()
    {
        return self::STATE_SUCCEEDED === $this->state;
    }

    public function getName()
    {
        return $this->activityName;
    }

    public function __toString()
    {
        return sprintf('ActivityTask(id = %s, name = %s, state = %s)', $this->id, $this->activityName, $this->state);
    }
}