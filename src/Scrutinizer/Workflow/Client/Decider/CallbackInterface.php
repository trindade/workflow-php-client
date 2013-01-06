<?php

namespace Scrutinizer\Workflow\Client\Decider;

use Scrutinizer\Workflow\Client\Transport\WorkflowExecution;

interface CallbackInterface
{
    /**
     * Coordinates the workflow execution.
     *
     * @param \Scrutinizer\Workflow\Client\Transport\WorkflowExecution $execution
     * @param DecisionsBuilder $builder
     *
     * @return void
     */
    public function handle(WorkflowExecution $execution, DecisionsBuilder $builder);

    /**
     * Cleans up any resources which might have been allocated in handle().
     *
     * @return void
     */
    public function cleanUp();
}