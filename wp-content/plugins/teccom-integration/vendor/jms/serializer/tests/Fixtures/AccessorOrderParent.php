<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Fixtures;

use JMS\Serializer\Annotation as Serializer;

/** @Serializer\AccessorOrder("alphabetical") */
#[Serializer\AccessorOrder(order: 'alphabetical')]
class AccessorOrderParent
{
    private $b = 'b', $a = 'a';
}
