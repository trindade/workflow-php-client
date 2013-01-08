<?php

namespace Scrutinizer\Workflow\Client\Transport;

use JMS\Serializer\Annotation as Serializer;

class WorkflowExecutionTask extends AbstractActivityTask
{
    /**
     * @Serializer\Type("Scrutinizer\Workflow\Client\Transport\WorkflowExecution")
     * @Serializer\SerializedName("child_workflow_execution")
     *
     * @var WorkflowExecution
     */
    public $execution;

    public function isOpen()
    {
        return $this->execution->isOpen();
    }

    public function hasSucceeded()
    {
        return $this->execution->hasSucceeded();
    }

    public function hasFailed()
    {
        return $this->execution->hasFailed();
    }

    public function getName()
    {
        return $this->execution->workflowName;
    }
}