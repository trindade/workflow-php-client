<?php

namespace Scrutinizer\Workflow\Client\Decider;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Exclusion\GroupsExclusionStrategy;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;

abstract class AbstractCallback implements CallbackInterface
{
    private $serializer;

    public function __construct(Serializer $serializer = null)
    {
        $this->serializer = $serializer ?: SerializerBuilder::create()->build();
    }

    public function cleanUp()
    {
    }

    protected function serialize($data, array $groups = array())
    {
        return $this->serializer->serialize($data, 'json', SerializationContext::create()->setGroups($groups));
    }

    protected function deserialize($data, $type, array $groups = array())
    {
        return $this->serializer->deserialize($data, $type, 'json', DeserializationContext::create()->setGroups($groups));
    }
}
