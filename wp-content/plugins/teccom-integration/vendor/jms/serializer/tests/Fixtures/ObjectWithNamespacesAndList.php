<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Fixtures;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\XmlElement;
use JMS\Serializer\Annotation\XmlList;
use JMS\Serializer\Annotation\XmlMap;
use JMS\Serializer\Annotation\XmlNamespace;
use JMS\Serializer\Annotation\XmlRoot;

/**
 * @XmlRoot("ObjectWithNamespacesAndList", namespace="http://example.com/namespace")
 * @XmlNamespace(uri="http://example.com/namespace")
 * @XmlNamespace(uri="http://example.com/namespace2", prefix="x")
 */
#[XmlRoot(name: 'ObjectWithNamespacesAndList', namespace: 'http://example.com/namespace')]
#[XmlNamespace(uri: 'http://example.com/namespace')]
#[XmlNamespace(uri: 'http://example.com/namespace2', prefix: 'x')]
class ObjectWithNamespacesAndList
{
    /**
     * @Type("string")
     * @SerializedName("name")
     * @XmlElement(namespace="http://example.com/namespace")
     */
    #[Type(name: 'string')]
    #[SerializedName(name: 'name')]
    #[XmlElement(namespace: 'http://example.com/namespace')]
    public $name;

    /**
     * @Type("string")
     * @SerializedName("name")
     * @XmlElement(namespace="http://example.com/namespace2")
     */
    #[Type(name: 'string')]
    #[SerializedName(name: 'name')]
    #[XmlElement(namespace: 'http://example.com/namespace2')]
    public $nameAlternativeB;

    /**
     * @Type("array<string>")
     * @SerializedName("phones")
     * @XmlElement(namespace="http://example.com/namespace2")
     * @XmlList(inline = false, entry = "phone", namespace="http://example.com/namespace2")
     */
    #[Type(name: 'array<string>')]
    #[SerializedName(name: 'phones')]
    #[XmlElement(namespace: 'http://example.com/namespace2')]
    #[XmlList(entry: 'phone', inline: false, namespace: 'http://example.com/namespace2')]
    public $phones;

    /**
     * @Type("array<string,string>")
     * @SerializedName("addresses")
     * @XmlElement(namespace="http://example.com/namespace2")
     * @XmlMap(inline = false, entry = "address", keyAttribute = "id", namespace="http://example.com/namespace2")
     */
    #[Type(name: 'array<string,string>')]
    #[SerializedName(name: 'addresses')]
    #[XmlElement(namespace: 'http://example.com/namespace2')]
    #[XmlMap(keyAttribute: 'id', entry: 'address', inline: false, namespace: 'http://example.com/namespace2')]
    public $addresses;

    /**
     * @Type("array<string>")
     * @SerializedName("phones")
     * @XmlList(inline = true, entry = "phone", namespace="http://example.com/namespace")
     */
    #[Type(name: 'array<string>')]
    #[SerializedName(name: 'phones')]
    #[XmlList(entry: 'phone', inline: true, namespace: 'http://example.com/namespace')]
    public $phonesAlternativeB;

    /**
     * @Type("array<string,string>")
     * @SerializedName("addresses")
     * @XmlMap(inline = true, entry = "address", keyAttribute = "id", namespace="http://example.com/namespace")
     */
    #[Type(name: 'array<string,string>')]
    #[SerializedName(name: 'addresses')]
    #[XmlMap(keyAttribute: 'id', entry: 'address', inline: true, namespace: 'http://example.com/namespace')]
    public $addressesAlternativeB;

    /**
     * @Type("array<string>")
     * @SerializedName("phones")
     * @XmlList(inline = true, entry = "phone",  namespace="http://example.com/namespace2")
     */
    #[Type(name: 'array<string>')]
    #[SerializedName(name: 'phones')]
    #[XmlList(entry: 'phone', inline: true, namespace: 'http://example.com/namespace2')]
    public $phonesAlternativeC;

    /**
     * @Type("array<string,string>")
     * @SerializedName("addresses")
     * @XmlMap(inline = true, entry = "address", keyAttribute = "id", namespace="http://example.com/namespace2")
     */
    #[Type(name: 'array<string,string>')]
    #[SerializedName(name: 'addresses')]
    #[XmlMap(keyAttribute: 'id', entry: 'address', inline: true, namespace: 'http://example.com/namespace2')]
    public $addressesAlternativeC;

    /**
     * @Type("array<string>")
     * @SerializedName("phones")
     * @XmlList(inline = false, entry = "phone")
     */
    #[Type(name: 'array<string>')]
    #[SerializedName(name: 'phones')]
    #[XmlList(entry: 'phone', inline: false)]
    public $phonesAlternativeD;
    /**
     * @Type("array<string,string>")
     * @SerializedName("addresses")
     * @XmlMap(inline = false, entry = "address", keyAttribute = "id")
     */
    #[Type(name: 'array<string,string>')]
    #[SerializedName(name: 'addresses')]
    #[XmlMap(keyAttribute: 'id', entry: 'address', inline: false)]
    public $addressesAlternativeD;
}
