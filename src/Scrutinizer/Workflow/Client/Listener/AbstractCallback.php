<?php

namespace Scrutinizer\Workflow\Client\Listener;

abstract class AbstractCallback implements CallbackInterface
{
    public function getSubscribedEvents()
    {
        return array();
    }

    public function getSubscribedWorkflows()
    {
        return array();
    }

    public function initialize()
    {
    }

    public function cleanUp()
    {
    }
}