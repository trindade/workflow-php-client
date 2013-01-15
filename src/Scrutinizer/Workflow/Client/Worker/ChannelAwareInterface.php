<?php

namespace Scrutinizer\Workflow\Client\Worker;

use PhpAmqpLib\Channel\AMQPChannel;

interface ChannelAwareInterface
{
    public function setChannel(AMQPChannel $channel);
}