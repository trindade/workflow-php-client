<?php

namespace Scrutinizer\Workflow\Client\Annotation;

/**
 * @Annotation
 */
final class Type
{
    /** @var string @Required */
    public $name;

    /** @var string @Required */
    public $deciderQueueName;

    /** @var array<ActivityType> @Required */
    public $activities = array();
}