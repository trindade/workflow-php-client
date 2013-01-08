<?php

namespace Scrutinizer\Workflow\Client\Activity;

use JMS\Serializer\Exclusion\GroupsExclusionStrategy;
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
        $this->serializer->setExclusionStrategy(empty($groups) ? null : new GroupsExclusionStrategy($groups));

        return $this->serializer->serialize($data, 'json');
    }

    protected function deserialize($data, $type, array $groups = array())
    {
        $this->serializer->setExclusionStrategy(empty($groups) ? null : new GroupsExclusionStrategy($groups));

        return $this->serializer->deserialize($data, $type, 'json');
    }
}