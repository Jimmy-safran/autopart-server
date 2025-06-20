<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Fixtures;

use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Type;

/** blablub */
class GroupsObject
{
    /**
     * @Groups({"foo"})
     * @Type("string")
     */
    #[Groups(groups: ['foo'])]
    #[Type(name: 'string')]
    private $foo;

    /**
     * @Groups({"foo","bar"})
     * @Type("string")
     */
    #[Groups(groups: ['foo', 'bar'])]
    #[Type(name: 'string')]
    private $foobar;

    /**
     * @Groups({"bar", "Default"})
     * @Type("string")
     */
    #[Groups(groups: ['bar', 'Default'])]
    #[Type(name: 'string')]
    private $bar;

    /**
     * @Type("string")
     */
    #[Type(name: 'string')]
    private $none;

    public function __construct()
    {
        $this->foo = 'foo';
        $this->bar = 'bar';
        $this->foobar = 'foobar';
        $this->none = 'none';
    }
}
