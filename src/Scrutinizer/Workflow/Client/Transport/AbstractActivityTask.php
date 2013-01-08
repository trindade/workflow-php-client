<?php

namespace Scrutinizer\Workflow\Client\Transport;

use JMS\Serializer\Annotation as Serializer;

abstract class AbstractActivityTask extends AbstractTask
{
    /** @Serializer\Type("array") */
    public $control;

    abstract public function hasSucceeded();
    abstract public function hasFailed();
    abstract public function getName();
}