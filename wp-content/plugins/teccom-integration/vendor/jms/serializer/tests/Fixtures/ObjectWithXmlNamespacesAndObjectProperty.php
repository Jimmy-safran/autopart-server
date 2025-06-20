<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Fixtures;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlElement;
use JMS\Serializer\Annotation\XmlNamespace;
use JMS\Serializer\Annotation\XmlRoot;

/**
 * @XmlRoot("property:test-object", namespace="http://example.com/namespace-property")
 * @XmlNamespace(uri="http://example.com/namespace-property", prefix="property")
 */
#[XmlRoot(name: 'property:test-object', namespace: 'http://example.com/namespace-property')]
#[XmlNamespace(uri: 'http://example.com/namespace-property', prefix: 'property')]
class ObjectWithXmlNamespacesAndObjectProperty
{
    /**
     * @Type("string")
     * @XmlElement(namespace="http://example.com/namespace-property");
     */
    #[Type(name: 'string')]
    #[XmlElement(namespace: 'http://example.com/namespace-property')]
    private $title;

    /**
     * @Type("JMS\Serializer\Tests\Fixtures\ObjectWithXmlNamespacesAndObjectPropertyAuthor")
     * @XmlElement(namespace="http://example.com/namespace-property")
     */
    #[Type(name: 'JMS\Serializer\Tests\Fixtures\ObjectWithXmlNamespacesAndObjectPropertyAuthor')]
    #[XmlElement(namespace: 'http://example.com/namespace-property')]
    private $author;

    public function __construct($title, $author)
    {
        $this->title = $title;
        $this->author = $author;
    }
}
