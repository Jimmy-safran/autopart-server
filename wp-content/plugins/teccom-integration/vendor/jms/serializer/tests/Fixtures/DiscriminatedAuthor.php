<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Fixtures;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

class DiscriminatedAuthor
{
    /**
     * @Type("string")
     * @SerializedName("full_name")
     */
    #[Type(name: 'string')]
    #[SerializedName(name: 'full_name')]
    private $name;

    /**
     * @Type("string")
     * @SerializedName("objectType")
     */
    #[Type(name: 'string')]
    #[SerializedName(name: 'objectType')]
    private $objectType = 'author';

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getObjectType()
    {
        return $this->objectType;
    }
}
