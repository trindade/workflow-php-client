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

namespace Scrutinizer\Workflow\Client;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use PhpAmqpLib\Connection\AMQPConnection;
use Scrutinizer\RabbitMQ\Rpc\RpcClient;
use Scrutinizer\Workflow\Client\Annotation\ActivityType;
use Scrutinizer\Workflow\Client\Annotation\Type;
use Scrutinizer\Workflow\Client\Transport\ExecutionListing;

class WorkflowClient
{
    private $client;
    private $annotationReader;

    public function __construct(RpcClient $client)
    {
        $this->client = $client;
    }

    public function setAnnotationReader(Reader $reader)
    {
        $this->annotationReader = $reader;
    }

    /**
     * Schedules the execution of a command.
     *
     * @param string $workflowName The name of the workflow which was registered with the workflow server.
     * @param string $input The input may be any string that is understood by the decider associated with this workflow.
     * @param string[] $tags Some tags which should be applied to this execution.
     *
     * @return array An array of the form ["execution_id" => "123"]
     */
    public function startExecution($workflowName, $input, array $tags = array())
    {
        return $this->client->invoke('workflow_execution', array(
            'workflow' => $workflowName,
            'input' => $input,
            'tags' => $tags,
        ), 'array');
    }

    /**
     * @param string[] $workflowNames
     * @param string[] $tags
     * @param null|string $status "open", or "closed"
     * @param string $order "asc" or "desc"
     *
     * @return ExecutionListing
     */
    public function listExecutions(array $workflowNames = array(), array $tags = array(), $status = null, $order = 'desc', $page = 1, $perPage = 20)
    {
        return $this->client->invoke('workflow_execution_listing', array(
            'workflows' => $workflowNames,
            'tags' => $tags,
            'status' => $status,
            'order' => $order,
            'page' => $page,
            'per_page' => $perPage,
        ), 'Scrutinizer\Workflow\Client\Transport\ExecutionListing');
    }

    /**
     * Terminates the given workflow execution.
     *
     * @param string $executionId
     *
     * @return array
     */
    public function terminateExecution($executionId)
    {
        return $this->client->invoke('workflow_execution_termination', array(
            'execution_id' => $executionId,
        ), 'array');
    }

    public function declareWorkflow($className)
    {
        if ( ! class_exists('Doctrine\Common\Annotations\AnnotationReader')) {
            throw new \RuntimeException('declareWorkflow() requires the doctrine/common package.');
        }

        if (null === $this->annotationReader) {
            $this->annotationReader = new AnnotationReader();
        }

        $annotations = $this->annotationReader->getClassAnnotations(new \ReflectionClass($className));
        foreach ($annotations as $annot) {
            if ( ! $annot instanceof Type) {
                continue;
            }

            $this->declareWorkflowType($annot->name, $annot->deciderQueueName);
            foreach ($annot->activities as $activityType) {
                /** @var $activityType ActivityType */
                $this->declareActivityType($activityType->name, $activityType->queue);
            }
        }
    }

    /**
     * Declares a new workflow type.
     *
     * This method is indempotent.
     *
     * @param string $name
     * @param string $deciderQueueName
     */
    public function declareWorkflowType($name, $deciderQueueName)
    {
        return $this->client->invoke('workflow_type', array(
            'name' => $name,
            'decider_queue_name' => $deciderQueueName,
        ), 'array');
    }

    /**
     * Declares a new activity type.
     *
     * @param string $name
     * @param string $queueName
     */
    public function declareActivityType($name, $queueName)
    {
        return $this->client->invoke('workflow_activity_type', array(
            'name' => $name,
            'queue_name' => $queueName,
        ), 'array');
    }
}