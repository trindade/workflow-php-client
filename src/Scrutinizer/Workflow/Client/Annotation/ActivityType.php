<?php

namespace Scrutinizer\Workflow\Client\Annotation;

/**
 * @Annotation
 */
final class ActivityType
{
    /** @var string @Required */
    public $name;

    /** @var string @Required */
    public $queue;
}