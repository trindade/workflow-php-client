<?php

namespace Scrutinizer\Workflow\Client\Listener;

use Scrutinizer\Workflow\Client\Transport\Event;

interface CallbackInterface
{
    public function getSubscribedEvents();
    public function getSubscribedWorkflows();
    public function initialize();
    public function handle(Event $event);
    public function cleanUp();
}