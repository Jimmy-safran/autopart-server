<?php
namespace Goetas\XML\XSDReader\Schema\Element;

use Goetas\XML\XSDReader\Schema\Type\Type;
use Goetas\XML\XSDReader\Schema\Schema;

class Group implements Element, ElementHolder
{
    /**
     *
     * @var Schema
     */
    protected $schema;

    protected $doc;

    protected $name;

    protected $elements=array();

    public function __construct(Schema $schema, $name)
    {
        $this->schema = $schema;
        $this->name = $name;
    }
    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getElements()
    {
        return $this->elements;
    }

    public function addElement(Element $element)
    {
        $this->elements[] = $element;
    }

    public function getDoc()
    {
        return $this->doc;
    }

    public function setDoc($doc)
    {
        $this->doc = $doc;
        return $this;
    }

    public function getSchema()
    {
        return $this->schema;
    }


}
