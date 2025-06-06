<?php
namespace Goetas\XML\XSDReader\Schema\Attribute;

use Goetas\XML\XSDReader\Schema\Type\TypeNodeChild;

class AttributeReal extends TypeNodeChild implements AttributeItem
{

    protected $qualified = true;

    protected $nil = false;

    protected $ref;

    protected $use = self::USE_OPTIONAL;

    public function isQualified()
    {
        return $this->qualified;
    }

    public function setQualified($qualified)
    {
        $this->qualified = $qualified;
        return $this;
    }

    public function isNil()
    {
        return $this->nil;
    }

    public function setNil($nil)
    {
        $this->nil = $nil;
        return $this;
    }

    public function getUse()
    {
        return $this->use;
    }

    public function setUse($use)
    {
        $this->use = $use;
        return $this;
    }

}
