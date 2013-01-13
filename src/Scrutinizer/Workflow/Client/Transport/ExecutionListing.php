<?php

namespace Scrutinizer\Workflow\Client\Transport;

use JMS\Serializer\Annotation as Serializer;

class ExecutionListing
{
    /**
     * @Serializer\Type("array<Scrutinizer\Workflow\Client\Transport\WorkflowExecution>")
     * @var WorkflowExecution[]
     */
    public $executions;

    /** @Serializer\Type("integer") */
    public $count;

    /** @Serializer\Type("integer") */
    public $perPage;

    /** @Serializer\Type("integer") */
    public $page;
}