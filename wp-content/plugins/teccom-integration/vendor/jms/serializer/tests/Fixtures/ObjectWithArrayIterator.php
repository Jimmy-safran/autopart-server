<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Fixtures;

use JMS\Serializer\Annotation as Serializer;

class ObjectWithArrayIterator
{
    /**
     * @Serializer\Type("ArrayIterator<string,string>")
     * @Serializer\XmlKeyValuePairs
     *
     * @var \ArrayIterator
     */
    #[Serializer\Type(name: 'ArrayIterator<string,string>')]
    #[Serializer\XmlKeyValuePairs]
    public $iterator;

    public function __construct(\ArrayIterator $iterator)
    {
        $this->iterator = $iterator;
    }
}
