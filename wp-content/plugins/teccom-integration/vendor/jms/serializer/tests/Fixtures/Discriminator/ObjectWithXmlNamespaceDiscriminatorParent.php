<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Fixtures\Discriminator;

use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\Discriminator(field = "type", map = {
 *    "child": "JMS\Serializer\Tests\Fixtures\Discriminator\ObjectWithXmlNamespaceDiscriminatorChild"
 * })
 * @Serializer\XmlDiscriminator(namespace="http://example.com/", cdata=false)
 * @Serializer\XmlNamespace(prefix="foo", uri="http://example.com/")
 */
#[Serializer\Discriminator(field: 'type', map: ['child' => 'JMS\Serializer\Tests\Fixtures\Discriminator\ObjectWithXmlNamespaceDiscriminatorChild'])]
#[Serializer\XmlDiscriminator(cdata: false, namespace: 'http://example.com/')]
#[Serializer\XmlNamespace(uri: 'http://example.com/', prefix: 'foo')]
abstract class ObjectWithXmlNamespaceDiscriminatorParent
{
}
