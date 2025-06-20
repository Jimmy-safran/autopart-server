<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Fixtures;

use JMS\Serializer\Annotation\Type;

class CircularReferenceCollection
{
    /** @Type("string") */
    #[Type(name: 'string')]
    public $name = 'foo';

    /** @Type("array<JMS\Serializer\Tests\Fixtures\CircularReferenceCollection>") */
    #[Type(name: 'array<JMS\Serializer\Tests\Fixtures\CircularReferenceCollection>')]
    public $collection = [];
}
