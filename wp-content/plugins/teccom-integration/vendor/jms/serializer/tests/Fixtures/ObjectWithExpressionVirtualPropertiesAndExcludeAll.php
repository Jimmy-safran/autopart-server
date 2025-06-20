<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Fixtures;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\VirtualProperty;

/**
 * @VirtualProperty(
 *     "virtualValue",
 *     exp="object.getVirtualValue()"
 * )
 * @ExclusionPolicy("all")
 */
#[VirtualProperty(name: 'virtualValue', exp: 'object.getVirtualValue()')]
#[ExclusionPolicy(policy: 'all')]
class ObjectWithExpressionVirtualPropertiesAndExcludeAll
{
    public function getVirtualValue()
    {
        return 'value';
    }
}
