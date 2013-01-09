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

    /** @var array<Scrutinizer\Workflow\Client\Annotation\ActivityType> @Required */
    public $activities = array();
}