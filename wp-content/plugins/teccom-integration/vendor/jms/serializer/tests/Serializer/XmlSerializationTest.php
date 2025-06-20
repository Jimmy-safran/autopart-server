<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\Exception\InvalidArgumentException;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Exception\XmlErrorException;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\DateHandler;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\Driver\TypedPropertiesDriver;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\Tests\Fixtures\AccessorSetter;
use JMS\Serializer\Tests\Fixtures\Discriminator\ObjectWithXmlAttributeDiscriminatorChild;
use JMS\Serializer\Tests\Fixtures\Discriminator\ObjectWithXmlAttributeDiscriminatorParent;
use JMS\Serializer\Tests\Fixtures\Discriminator\ObjectWithXmlNamespaceAttributeDiscriminatorChild;
use JMS\Serializer\Tests\Fixtures\Discriminator\ObjectWithXmlNamespaceAttributeDiscriminatorParent;
use JMS\Serializer\Tests\Fixtures\Discriminator\ObjectWithXmlNamespaceDiscriminatorChild;
use JMS\Serializer\Tests\Fixtures\Discriminator\ObjectWithXmlNamespaceDiscriminatorParent;
use JMS\Serializer\Tests\Fixtures\Discriminator\ObjectWithXmlNotCDataDiscriminatorChild;
use JMS\Serializer\Tests\Fixtures\Discriminator\ObjectWithXmlNotCDataDiscriminatorParent;
use JMS\Serializer\Tests\Fixtures\Input;
use JMS\Serializer\Tests\Fixtures\InvalidUsageOfXmlValue;
use JMS\Serializer\Tests\Fixtures\ObjectWithAttributeSyntax;
use JMS\Serializer\Tests\Fixtures\ObjectWithAttributeSyntaxWithoutNs;
use JMS\Serializer\Tests\Fixtures\ObjectWithElementAttributeSyntax;
use JMS\Serializer\Tests\Fixtures\ObjectWithElementAttributeSyntaxWithoutNs;
use JMS\Serializer\Tests\Fixtures\ObjectWithFloatProperty;
use JMS\Serializer\Tests\Fixtures\ObjectWithNamespacesAndList;
use JMS\Serializer\Tests\Fixtures\ObjectWithNamespacesAndNestedList;
use JMS\Serializer\Tests\Fixtures\ObjectWithVirtualXmlProperties;
use JMS\Serializer\Tests\Fixtures\ObjectWithXmlKeyValuePairs;
use JMS\Serializer\Tests\Fixtures\ObjectWithXmlKeyValuePairsWithObjectType;
use JMS\Serializer\Tests\Fixtures\ObjectWithXmlKeyValuePairsWithType;
use JMS\Serializer\Tests\Fixtures\ObjectWithXmlNamespaces;
use JMS\Serializer\Tests\Fixtures\ObjectWithXmlNamespacesAndObjectProperty;
use JMS\Serializer\Tests\Fixtures\ObjectWithXmlNamespacesAndObjectPropertyAuthor;
use JMS\Serializer\Tests\Fixtures\ObjectWithXmlNamespacesAndObjectPropertyVirtual;
use JMS\Serializer\Tests\Fixtures\ObjectWithXmlRootNamespace;
use JMS\Serializer\Tests\Fixtures\Person;
use JMS\Serializer\Tests\Fixtures\PersonCollection;
use JMS\Serializer\Tests\Fixtures\PersonLocation;
use JMS\Serializer\Tests\Fixtures\SimpleClassObject;
use JMS\Serializer\Tests\Fixtures\SimpleSubClassObject;
use JMS\Serializer\Tests\Fixtures\TypedProperties\UnionTypedProperties;
use JMS\Serializer\Visitor\Factory\XmlDeserializationVisitorFactory;
use JMS\Serializer\Visitor\Factory\XmlSerializationVisitorFactory;
use JMS\Serializer\XmlSerializationVisitor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

class XmlSerializationTest extends BaseSerializationTestCase
{
    public function testInvalidUsageOfXmlValue()
    {
        $obj = new InvalidUsageOfXmlValue();

        $this->expectException(RuntimeException::class);

        $this->serialize($obj);
    }

    /**
     * @dataProvider getXMLBooleans
     */
    #[DataProvider('getXMLBooleans')]
    public function testXMLBooleans($xmlBoolean, $boolean)
    {
        if ($this->hasDeserializer()) {
            self::assertSame($boolean, $this->deserialize('<result>' . $xmlBoolean . '</result>', 'boolean'));
        }
    }

    public static function getXMLBooleans()
    {
        return [['true', true], ['false', false], ['1', true], ['0', false]];
    }

    public function testAccessorSetterDeserialization()
    {
        $object = $this->deserialize(
            '<?xml version="1.0"?>
            <AccessorSetter>
                <element attribute="attribute">element</element>
                <collection>
                    <entry>collectionEntry</entry>
                </collection>
            </AccessorSetter>',
            'JMS\Serializer\Tests\Fixtures\AccessorSetter',
        );
        \assert($object instanceof AccessorSetter);

        self::assertInstanceOf('stdClass', $object->getElement());
        self::assertInstanceOf('JMS\Serializer\Tests\Fixtures\AccessorSetterElement', $object->getElement()->element);
        self::assertEquals('attribute-different', $object->getElement()->element->getAttribute());
        self::assertEquals('element-different', $object->getElement()->element->getElement());
        self::assertEquals(['collectionEntry' => 'collectionEntry'], $object->getCollection());
    }

    public function testPropertyIsObjectWithAttributeAndValue()
    {
        $personCollection = new PersonLocation();
        $person = new Person();
        $person->name = 'Matthias Noback';
        $person->age = 28;
        $personCollection->person = $person;
        $personCollection->location = 'The Netherlands';

        self::assertEquals(self::getContent('person_location'), $this->serialize($personCollection));
    }

    public function testPropertyIsCollectionOfObjectsWithAttributeAndValue()
    {
        $personCollection = new PersonCollection();
        $person = new Person();
        $person->name = 'Matthias Noback';
        $person->age = 28;
        $personCollection->persons->add($person);
        $personCollection->location = 'The Netherlands';

        self::assertEquals(self::getContent('person_collection'), $this->serialize($personCollection));
    }

    public function testExternalEntitiesAreDisabledByDefault()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The document type "<!DOCTYPE author [<!ENTITY foo SYSTEM "php://filter/read=convert.base64-encode/resource=XmlSerializationTest.php">]>" is not allowed. If it is safe, you may add it to the allowlist configuration.');

        $this->deserialize('<?xml version="1.0"?>
            <!DOCTYPE author [
                <!ENTITY foo SYSTEM "php://filter/read=convert.base64-encode/resource=' . basename(__FILE__) . '">
            ]>
            <result>
                &foo;
            </result>', 'stdClass');
    }

    public function testDocumentTypesAreNotAllowed()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The document type "<!DOCTYPE foo>" is not allowed. If it is safe, you may add it to the allowlist configuration.');

        $this->deserialize('<?xml version="1.0"?><!DOCTYPE foo><foo></foo>', 'stdClass');
    }

    /**
     * @doesNotPerformAssertions
     */
    #[DoesNotPerformAssertions]
    public function testWhitelistedDocumentTypesAreAllowed()
    {
        $xmlVisitor = new XmlDeserializationVisitorFactory();

        $xmlVisitor->setDoctypeWhitelist([
            '<!DOCTYPE authorized SYSTEM "http://authorized_url.dtd">',
            '<!DOCTYPE author [<!ENTITY foo SYSTEM "php://filter/read=convert.base64-encode/resource=' . basename(__FILE__) . '">]>',
        ]);

        $builder = SerializerBuilder::create();
        $builder->setDeserializationVisitor('xml', $xmlVisitor);
        $serializer = $builder->build();

        $serializer->deserialize('<?xml version="1.0"?>
            <!DOCTYPE authorized SYSTEM "http://authorized_url.dtd">
            <foo></foo>', 'stdClass', 'xml');

        $serializer->deserialize('<?xml version="1.0"?>
            <!DOCTYPE author [
                <!ENTITY foo SYSTEM "php://filter/read=convert.base64-encode/resource=' . basename(__FILE__) . '">
            ]>
            <foo></foo>', 'stdClass', 'xml');
    }

    public function testVirtualAttributes()
    {
        self::assertEquals(
            self::getContent('virtual_attributes'),
            $this->serialize(new ObjectWithVirtualXmlProperties(), SerializationContext::create()->setGroups(['attributes'])),
        );
    }

    public function testVirtualValues()
    {
        self::assertEquals(
            self::getContent('virtual_values'),
            $this->serialize(new ObjectWithVirtualXmlProperties(), SerializationContext::create()->setGroups(['values'])),
        );
    }

    public function testVirtualXmlList()
    {
        self::assertEquals(
            self::getContent('virtual_properties_list'),
            $this->serialize(new ObjectWithVirtualXmlProperties(), SerializationContext::create()->setGroups(['list'])),
        );
    }

    public function testVirtualXmlMap()
    {
        self::assertEquals(
            self::getContent('virtual_properties_map'),
            $this->serialize(new ObjectWithVirtualXmlProperties(), SerializationContext::create()->setGroups(['map'])),
        );
    }

    public function testUnserializeMissingArray()
    {
        $xml = '<result></result>';
        $object = $this->serializer->deserialize($xml, 'JMS\Serializer\Tests\Fixtures\ObjectWithAbsentXmlListNode', 'xml');
        self::assertEquals([], $object->absentAndNs);

        $xml = '<result xmlns:x="http://www.example.com">
                    <absent_and_ns>
                        <x:entry>foo</x:entry>
                    </absent_and_ns>
                  </result>';
        $object = $this->serializer->deserialize($xml, 'JMS\Serializer\Tests\Fixtures\ObjectWithAbsentXmlListNode', 'xml');
        self::assertEquals(['foo'], $object->absentAndNs);
    }

    public function testObjectWithNamespacesAndList()
    {
        $object = new ObjectWithNamespacesAndList();
        $object->name = 'name';
        $object->nameAlternativeB = 'nameB';

        $object->phones = ['111', '222'];
        $object->addresses = ['A' => 'Street 1', 'B' => 'Street 2'];

        $object->phonesAlternativeB = ['555', '666'];
        $object->addressesAlternativeB = ['A' => 'Street 5', 'B' => 'Street 6'];

        $object->phonesAlternativeC = ['777', '888'];
        $object->addressesAlternativeC = ['A' => 'Street 7', 'B' => 'Street 8'];

        $object->phonesAlternativeD = ['999', 'AAA'];
        $object->addressesAlternativeD = ['A' => 'Street 9', 'B' => 'Street A'];

        self::assertEquals(
            self::getContent('object_with_namespaces_and_list'),
            $this->serialize($object, SerializationContext::create()),
        );
        self::assertEquals(
            $object,
            $this->deserialize(self::getContent('object_with_namespaces_and_list'), get_class($object)),
        );
    }

    public function testObjectWithNamespaceAndNestedList()
    {
        $object = new ObjectWithNamespacesAndNestedList();
        $personCollection = new PersonCollection();
        $personA = new Person();
        $personA->age = 11;
        $personA->name = 'AAA';

        $personB = new Person();
        $personB->age = 22;
        $personB->name = 'BBB';

        $personCollection->persons->add($personA);
        $personCollection->persons->add($personB);

        $object->personCollection = $personCollection;

        self::assertEquals(
            self::getContent('object_with_namespaces_and_nested_list'),
            $this->serialize($object, SerializationContext::create()),
        );
        self::assertEquals(
            $object,
            $this->deserialize(self::getContent('object_with_namespaces_and_nested_list'), get_class($object)),
        );
    }

    public function testArrayKeyValues()
    {
        self::assertEquals(self::getContent('array_key_values'), $this->serializer->serialize(new ObjectWithXmlKeyValuePairs(), 'xml'));
    }

    public function testDeserializeArrayKeyValues()
    {
        $xml = self::getContent('array_key_values_with_type_1');
        $result = $this->serializer->deserialize($xml, ObjectWithXmlKeyValuePairsWithType::class, 'xml');

        self::assertInstanceOf(ObjectWithXmlKeyValuePairsWithType::class, $result);
        self::assertEquals(ObjectWithXmlKeyValuePairsWithType::create1(), $result);

        $xml2 = self::getContent('array_key_values_with_type_2');
        $result2 = $this->serializer->deserialize($xml2, ObjectWithXmlKeyValuePairsWithType::class, 'xml');

        self::assertInstanceOf(ObjectWithXmlKeyValuePairsWithType::class, $result2);
        self::assertEquals(ObjectWithXmlKeyValuePairsWithType::create2(), $result2);
    }

    public function testDeserializeTypedAndNestedArrayKeyValues()
    {
        $xml = self::getContent('array_key_values_with_nested_type');
        $result = $this->serializer->deserialize($xml, ObjectWithXmlKeyValuePairsWithObjectType::class, 'xml');

        self::assertInstanceOf(ObjectWithXmlKeyValuePairsWithObjectType::class, $result);
        self::assertEquals(ObjectWithXmlKeyValuePairsWithObjectType::create1(), $result);
    }

    /**
     * @dataProvider getDateTime
     * @group datetime
     */
    #[DataProvider('getDateTime')]
    public function testDateTimeNoCData($key, $value, $type)
    {
        $builder = SerializerBuilder::create();
        $builder->configureHandlers(static function (HandlerRegistryInterface $handlerRegistry) {
            $handlerRegistry->registerSubscribingHandler(new DateHandler(\DateTime::ATOM, 'UTC', false));
        });
        $serializer = $builder->build();

        self::assertEquals(self::getContent($key . '_no_cdata'), $serializer->serialize($value, $this->getFormat()));
    }

    /**
     * @dataProvider getDateTimeImmutable
     * @group datetime
     */
    #[DataProvider('getDateTimeImmutable')]
    public function testDateTimeImmutableNoCData($key, $value, $type)
    {
        $builder = SerializerBuilder::create();
        $builder->configureHandlers(static function (HandlerRegistryInterface $handlerRegistry) {
            $handlerRegistry->registerSubscribingHandler(new DateHandler(\DateTime::ATOM, 'UTC', false));
        });
        $serializer = $builder->build();

        self::assertEquals(self::getContent($key . '_no_cdata'), $serializer->serialize($value, $this->getFormat()));
    }

    public function testXmlAttributeMapWithoutArray()
    {
        $attributes = new \ArrayObject(['type' => 'text']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported value type for XML attribute map. Expected array but got object');

        $this->serializer->serialize(new Input($attributes), $this->getFormat());
    }

    public function testObjectWithOnlyNamespacesAndList()
    {
        $object = new ObjectWithNamespacesAndList();

        $object->phones = [];
        $object->addresses = [];

        $object->phonesAlternativeB = [];
        $object->addressesAlternativeB = [];

        $object->phonesAlternativeC = ['777', '888'];
        $object->addressesAlternativeC = ['A' => 'Street 7', 'B' => 'Street 8'];

        $object->phonesAlternativeD = [];
        $object->addressesAlternativeD = [];

        self::assertEquals(
            self::getContent('object_with_only_namespaces_and_list'),
            $this->serialize($object, SerializationContext::create()),
        );

        $deserialized = $this->deserialize(self::getContent('object_with_only_namespaces_and_list'), get_class($object));
        self::assertEquals($object, $deserialized);
    }

    public function testDeserializingNull()
    {
        $this->markTestSkipped('Not supported in XML.');
    }

    public function testObjectWithXmlNamespaces()
    {
        $object = new ObjectWithXmlNamespaces('This is a nice title.', 'Foo Bar', new \DateTime('2011-07-30 00:00', new \DateTimeZone('UTC')), 'en', 'value for empty namespace property');

        $serialized = $this->serialize($object);
        self::assertEquals(self::getContent('object_with_xml_namespaces'), $serialized);

        $xml = simplexml_load_string($this->serialize($object));
        $xml->registerXPathNamespace('ns1', 'http://purl.org/dc/elements/1.1/');
        $xml->registerXPathNamespace('ns2', 'http://schemas.google.com/g/2005');
        $xml->registerXPathNamespace('ns3', 'http://www.w3.org/2005/Atom');

        self::assertEquals('2011-07-30T00:00:00+00:00', $this->xpathFirstToString($xml, './@created_at'));
        self::assertEquals('e86ce85cdb1253e4fc6352f5cf297248bceec62b', $this->xpathFirstToString($xml, './@ns2:etag'));
        self::assertEquals('en', $this->xpathFirstToString($xml, './@ns1:language'));
        self::assertEquals('This is a nice title.', $this->xpathFirstToString($xml, './ns1:title'));
        self::assertEquals('Foo Bar', $this->xpathFirstToString($xml, './ns3:author'));
        self::assertEquals('value for empty namespace property', $this->xpathFirstToString($xml, './empty_ns_element'));

        $deserialized = $this->deserialize(self::getContent('object_with_xml_namespacesalias'), get_class($object));
        self::assertEquals('2011-07-30T00:00:00+00:00', $this->getField($deserialized, 'createdAt')->format(\DateTime::ATOM));
        self::assertSame('This is a nice title.', $this->getField($deserialized, 'title'));
        self::assertSame('e86ce85cdb1253e4fc6352f5cf297248bceec62b', $this->getField($deserialized, 'etag'));
        self::assertSame('en', $this->getField($deserialized, 'language'));
        self::assertSame('Foo Bar', $this->getField($deserialized, 'author'));
        self::assertEquals('value for empty namespace property', $this->getField($deserialized, 'emptyNsElement'));
    }

    public function testObjectWithXmlNamespacesAndBackReferencedNamespaces()
    {
        $author = new ObjectWithXmlNamespacesAndObjectPropertyAuthor('mr', 'smith');
        $object = new ObjectWithXmlNamespacesAndObjectProperty('This is a nice title.', $author);

        $serialized = $this->serialize($object);
        self::assertEquals(self::getContent('object_with_xml_namespaces_and_object_property'), $serialized);
    }

    public function testObjectWithXmlNamespacesAndBackReferencedNamespacesWithListeners()
    {
        $author = new ObjectWithXmlNamespacesAndObjectPropertyAuthor('mr', 'smith');
        $object = new ObjectWithXmlNamespacesAndObjectPropertyVirtual('This is a nice title.', new \stdClass());

        $this->handlerRegistry->registerHandler(
            GraphNavigatorInterface::DIRECTION_SERIALIZATION,
            'ObjectWithXmlNamespacesAndObjectPropertyAuthorVirtual',
            $this->getFormat(),
            static function (XmlSerializationVisitor $visitor, $data, $type, Context $context) use ($author) {
                $factory = $context->getMetadataFactory();
                $classMetadata = $factory->getMetadataForClass(get_class($author));
                \assert($classMetadata instanceof ClassMetadata);

                $metadata = new StaticPropertyMetadata(get_class($author), 'foo', $author);
                $metadata->xmlNamespace = $classMetadata->xmlRootNamespace;

                $visitor->visitProperty($metadata, $author);
            },
        );

        $serialized = $this->serialize($object);
        self::assertEquals(self::getContent('object_with_xml_namespaces_and_object_property_virtual'), $serialized);
    }

    public function testObjectWithXmlRootNamespace()
    {
        $object = new ObjectWithXmlRootNamespace('This is a nice title.', 'Foo Bar', new \DateTime('2011-07-30 00:00', new \DateTimeZone('UTC')), 'en', 'value for empty namespace property');
        self::assertEquals(self::getContent('object_with_xml_root_namespace'), $this->serialize($object));
    }

    public function testXmlNamespacesInheritance()
    {
        $object = new SimpleClassObject();
        $object->foo = 'foo';
        $object->bar = 'bar';
        $object->moo = 'moo';

        self::assertEquals(self::getContent('simple_class_object'), $this->serialize($object));

        $childObject = new SimpleSubClassObject();
        $childObject->foo = 'foo';
        $childObject->bar = 'bar';
        $childObject->moo = 'moo';
        $childObject->baz = 'baz';
        $childObject->qux = 'qux';

        self::assertEquals(self::getContent('simple_subclass_object'), $this->serialize($childObject));
    }

    public function testWithoutFormatedOutputByXmlSerializationVisitor()
    {
        $xmlVisitor = new XmlSerializationVisitorFactory();
        $xmlVisitor->setFormatOutput(false);

        $builder = SerializerBuilder::create();
        $builder->setSerializationVisitor('xml', $xmlVisitor);
        $serializer = $builder->build();

        $object = new SimpleClassObject();
        $object->foo = 'foo';
        $object->bar = 'bar';
        $object->moo = 'moo';

        $stringXml = $serializer->serialize($object, $this->getFormat());
        self::assertXmlStringEqualsXmlString(self::getContent('simple_class_object_minified'), $stringXml);
    }

    public function testDiscriminatorAsXmlAttribute()
    {
        $xml = $this->serialize(new ObjectWithXmlAttributeDiscriminatorChild());
        self::assertEquals(self::getContent('xml_discriminator_attribute'), $xml);
        self::assertInstanceOf(
            ObjectWithXmlAttributeDiscriminatorChild::class,
            $this->deserialize(
                $xml,
                ObjectWithXmlAttributeDiscriminatorParent::class,
            ),
        );
    }

    public function testDiscriminatorAsNotCData()
    {
        $xml = $this->serialize(new ObjectWithXmlNotCDataDiscriminatorChild());
        self::assertEquals(self::getContent('xml_discriminator_not_cdata'), $xml);
        self::assertInstanceOf(
            ObjectWithXmlNotCDataDiscriminatorChild::class,
            $this->deserialize(
                $xml,
                ObjectWithXmlNotCDataDiscriminatorParent::class,
            ),
        );
    }

    public function testDiscriminatorWithNamespace()
    {
        $xml = $this->serialize(new ObjectWithXmlNamespaceDiscriminatorChild());
        self::assertEquals(self::getContent('xml_discriminator_namespace'), $xml);

        self::assertInstanceOf(
            ObjectWithXmlNamespaceDiscriminatorChild::class,
            $this->deserialize(
                $xml,
                ObjectWithXmlNamespaceDiscriminatorParent::class,
            ),
        );
    }

    public function testDiscriminatorAsXmlAttributeWithNamespace()
    {
        $xml = $this->serialize(new ObjectWithXmlNamespaceAttributeDiscriminatorChild());
        self::assertEquals(self::getContent('xml_discriminator_namespace_attribute'), $xml);

        self::assertInstanceOf(
            ObjectWithXmlNamespaceAttributeDiscriminatorChild::class,
            $this->deserialize(
                $xml,
                ObjectWithXmlNamespaceAttributeDiscriminatorParent::class,
            ),
        );
    }

    public function testDeserializeEmptyString()
    {
        $this->expectException(XmlErrorException::class);

        $this->deserialize('', 'stdClass');
    }

    public function testEvaluatesToNull()
    {
        $visitor = (new XmlDeserializationVisitorFactory())->getVisitor();
        $xsdNilAsTrueElement = simplexml_load_string('<empty xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:nil="true"/>');
        $xsdNilAsOneElement = simplexml_load_string('<empty xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:nil="1"/>');

        self::assertTrue($visitor->isNull($xsdNilAsTrueElement));
        self::assertTrue($visitor->isNull($xsdNilAsOneElement));
        self::assertTrue($visitor->isNull(null));
    }

    public function testDoubleEncoding()
    {
        $visitor = (new XmlSerializationVisitorFactory())->getVisitor();

        // Setting locale with comma fractional separator
        $locale = setlocale(LC_ALL, 0);
        if (!setlocale(LC_ALL, 'ru_RU.UTF-8')) {
            $this->markTestIncomplete('Required locale not available');
        }

        self::assertEquals('0.0', $visitor->visitDouble(0, [])->data);
        self::assertEquals('1.0', $visitor->visitDouble(1, [])->data);
        self::assertEquals('1.1', $visitor->visitDouble(1.1, [])->data);
        self::assertEquals('1.123456789', $visitor->visitDouble(1.123456789, [])->data);

        // Switching locale back
        setlocale(LC_ALL, $locale);
    }

    public function testSerialisationWithPrecisionForFloat(): void
    {
        $objectWithFloat = new ObjectWithFloatProperty(
            1.555555555,
            1.555555555,
            1.555,
            1.15,
            1.15,
            1.555,
            1.5,
            1.555,
            1.555,
        );

        $result = $this->serialize($objectWithFloat, SerializationContext::create());

        static::assertXmlStringEqualsXmlString(
            '<?xml version="1.0" encoding="UTF-8"?>
            <result>
              <floating_point_unchanged>1.555555555</floating_point_unchanged>
              <floating_point_precision_zero>2.0</floating_point_precision_zero>
              <floating_point_half_down>1.55</floating_point_half_down>
              <floating_point_half_even>1.2</floating_point_half_even>
              <floating_point_half_odd>1.1</floating_point_half_odd>
              <floating_point_half_up>1.56</floating_point_half_up>
              <floating_point_fixed_decimals>1.50</floating_point_fixed_decimals>
              <floating_point_fixed_decimals_less>1.6</floating_point_fixed_decimals_less>
              <floating_point_fixed_decimals_more>1.560</floating_point_fixed_decimals_more>
            </result>',
            $result,
        );
    }

    public function testThrowingExceptionWhenDeserializingUnionProperties()
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped(sprintf('%s requires PHP 8.0', TypedPropertiesDriver::class));

            return;
        }

        $this->expectException(RuntimeException::class);

        $object = new UnionTypedProperties(10000, null, false);
        self::assertEquals($object, $this->deserialize(static::getContent('data_integer'), UnionTypedProperties::class));
    }

    public function testSerializeElementAttributeSyntax()
    {
        $object = new ObjectWithElementAttributeSyntax(
            'IDValue',
            'IDScheme',
            'CustomValue',
            'SpecialType',
            '123',
        );
        $object->descriptionValue = 'A description';
        $object->descriptionLang = 'en';
        $object->dataPointUnit = 'kg';
        $object->nullableIdentifierValue = 'NullableValue';
        $object->nullableIdentifierScheme = 'NullableScheme';

        $expectedXml = self::getContent('object_with_element_attribute_syntax');
        $actualXml = $this->serialize($object);

        self::assertXmlStringEqualsXmlString($expectedXml, $actualXml);
        $deserializedObject = $this->deserialize($actualXml, ObjectWithElementAttributeSyntax::class);
        self::assertEquals($object, $deserializedObject);
    }

    public function testSerializeElementAttributeSyntaxWithNulls()
    {
        $object = new ObjectWithElementAttributeSyntax(
            'IDValueOnly',
            'IDSchemeOnly',
            'CustomValueOnly',
            'SpecialTypeOnly',
            '123NoUnit',
        );
        $object->nullableIdentifierValue = 'ValueWithoutScheme';

        $expectedXml = self::getContent('object_with_element_attribute_syntax_nulls');
        $actualXml = $this->serialize($object);
        self::assertXmlStringEqualsXmlString($expectedXml, $actualXml);

        $deserializedObject = $this->deserialize($actualXml, ObjectWithElementAttributeSyntax::class);
        self::assertEquals($object, $deserializedObject);
    }

    public function testSerializeElementAttributeSyntaxWithoutNs()
    {
        $object = new ObjectWithElementAttributeSyntaxWithoutNs(
            'IDValue',
            'IDScheme',
            'CustomValue',
        );
        $object->descriptionValue = 'A description';
        $object->descriptionLang = 'en';
        $object->dataPointUnit = 'kg';
        $object->nullableIdentifierValue = 'NullableValue';
        $object->nullableIdentifierScheme = 'NullableScheme';

        $actualXml = $this->serialize($object);
        static::assertXmlStringEqualsXmlString(
            '<?xml version="1.0" encoding="UTF-8"?>
            <test-object>
              <Identifier scheme="IDScheme">IDValue</Identifier>
              <Description lang="en">A description</Description>
              <DataPoint unit="kg">CustomValue</DataPoint>
              <NullableIdentifier scheme="NullableScheme">NullableValue</NullableIdentifier>
            </test-object>',
            $actualXml,
        );

        $deserializedObject = $this->deserialize($actualXml, ObjectWithElementAttributeSyntaxWithoutNs::class);
        self::assertEquals($object, $deserializedObject);
    }

    public function testSerializeElementAttributeSyntaxWithoutNsWithNulls()
    {
        $object = new ObjectWithElementAttributeSyntaxWithoutNs(
            'IDValueOnly',
            'IDSchemeOnly',
            'CustomValueOnly',
        );
        $object->nullableIdentifierValue = 'ValueWithoutScheme';

        $actualXml = $this->serialize($object);
        static::assertXmlStringEqualsXmlString(
            '<?xml version="1.0" encoding="UTF-8"?>
            <test-object>
              <Identifier scheme="IDSchemeOnly">IDValueOnly</Identifier>
              <DataPoint>CustomValueOnly</DataPoint>
              <NullableIdentifier>ValueWithoutScheme</NullableIdentifier>
            </test-object>',
            $actualXml,
        );

        $deserializedObject = $this->deserialize($actualXml, ObjectWithElementAttributeSyntaxWithoutNs::class);
        self::assertEquals($object, $deserializedObject);
    }

    public function testSerializeAttributeSyntax()
    {
        $object = new ObjectWithAttributeSyntax(
            'CustomValueOnly',
            'TestIdentifierValue',
            'NamespacedTestIdentifierValue',
        );

        $object->nullableIdentifierValue = 'NullableIdentifierValue';
        $object->nullableIdentifierScheme = 'NullableIdentifierScheme';

        $expectedXml = self::getContent('object_with_attribute_syntax');
        $actualXml = $this->serialize($object);
        self::assertXmlStringEqualsXmlString($expectedXml, $actualXml);

        $deserializedObject = $this->deserialize($actualXml, ObjectWithAttributeSyntax::class);
        self::assertEquals($object, $deserializedObject);
    }

    public function testSerializeAttributeSyntaxWithNulls()
    {
        $object = new ObjectWithAttributeSyntax(
            'CustomValueOnly',
            'TestIdentifierValue',
            'NamespacedTestIdentifierValue',
        );

        $object->nullableIdentifierValue = 'NullableIdentifierValue';
        $object->nullableIdentifierScheme = null;

        $expectedXml = self::getContent('object_with_attribute_syntax_nulls');
        $actualXml = $this->serialize($object);
        self::assertXmlStringEqualsXmlString($expectedXml, $actualXml);

        $deserializedObject = $this->deserialize($actualXml, ObjectWithAttributeSyntax::class);
        self::assertEquals($object, $deserializedObject);
    }

    public function testSerializeAttributeSyntaxWithoutNs()
    {
        $object = new ObjectWithAttributeSyntaxWithoutNs(
            'CustomValueOnly',
            'TestIdentifierValue',
        );

        $object->nullableIdentifierValue = 'NullableIdentifierValue';
        $object->nullableIdentifierScheme = 'NonNullableIdentifierScheme';

        $expectedXml = self::getContent('object_with_attribute_syntax_without_ns');
        $actualXml = $this->serialize($object);
        self::assertXmlStringEqualsXmlString($expectedXml, $actualXml);

        $deserializedObject = $this->deserialize($actualXml, ObjectWithAttributeSyntaxWithoutNs::class);
        self::assertEquals($object, $deserializedObject);
    }

    public function testSerializeAttributeSyntaxWithoutNsNulls()
    {
        $object = new ObjectWithAttributeSyntaxWithoutNs(
            'CustomValueOnly',
            'TestIdentifierValue',
        );

        $object->nullableIdentifierValue = 'NullableIdentifierValue';
        $object->nullableIdentifierScheme = null;

        $expectedXml = self::getContent('object_with_attribute_syntax_without_ns_nulls');
        $actualXml = $this->serialize($object);
        self::assertXmlStringEqualsXmlString($expectedXml, $actualXml);

        $deserializedObject = $this->deserialize($actualXml, ObjectWithAttributeSyntaxWithoutNs::class);
        self::assertEquals($object, $deserializedObject);
    }

    private function xpathFirstToString(\SimpleXMLElement $xml, $xpath)
    {
        $nodes = $xml->xpath($xpath);

        return (string) reset($nodes);
    }

    /**
     * @param string $key
     */
    protected static function getContent($key)
    {
        if (!file_exists($file = __DIR__ . '/xml/' . $key . '.xml')) {
            throw new InvalidArgumentException(sprintf('The key "%s" is not supported.', $key));
        }

        return file_get_contents($file);
    }

    protected function getFormat()
    {
        return 'xml';
    }
}
