<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Fixtures;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

class SimpleObject
{
    /** @Type("string") */
    #[Type(name: 'string')]
    private $foo;

    /**
     * @SerializedName("moo")
     * @Type("string")
     */
    #[SerializedName(name: 'moo')]
    #[Type(name: 'string')]
    private $bar;

    /** @Type("string") */
    #[Type(name: 'string')]
    protected $camelCase = 'boo';

    public function __construct($foo, $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }

    public function getFoo()
    {
        return $this->foo;
    }

    public function getBar()
    {
        return $this->bar;
    }
}
