<?php
namespace Goetas\XML\XSDReader\Schema\Type;

use Goetas\XML\XSDReader\Schema\Inheritance\Restriction;
class SimpleType extends Type
{

    /**
     *
     * @var Restriction
     */
    protected $restriction;

    /**
     *
     * @var SimpleType[]
     */
    protected $unions = array();

    /**
     *
     * @return Restriction
     */
    public function getRestriction()
    {
        return $this->restriction;
    }

    public function setRestriction(Restriction $restriction)
    {
        $this->restriction = $restriction;
        return $this;
    }

    public function addUnion(SimpleType $type)
    {
        $this->unions[] = $type;
        return $this;
    }

    public function getUnions()
    {
        return $this->unions;
    }

}
