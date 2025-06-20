<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Serializer;

use JMS\Serializer\Context;
use JMS\Serializer\EventDispatcher\Event;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Exception\NonVisitableTypeException;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Metadata\Driver\TypedPropertiesDriver;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Tests\Fixtures\Author;
use JMS\Serializer\Tests\Fixtures\AuthorList;
use JMS\Serializer\Tests\Fixtures\DataFalse;
use JMS\Serializer\Tests\Fixtures\DataTrue;
use JMS\Serializer\Tests\Fixtures\DiscriminatedAuthor;
use JMS\Serializer\Tests\Fixtures\DiscriminatedComment;
use JMS\Serializer\Tests\Fixtures\FirstClassMapCollection;
use JMS\Serializer\Tests\Fixtures\ObjectWithEmptyArrayAndHash;
use JMS\Serializer\Tests\Fixtures\ObjectWithInlineArray;
use JMS\Serializer\Tests\Fixtures\ObjectWithObjectProperty;
use JMS\Serializer\Tests\Fixtures\Tag;
use JMS\Serializer\Tests\Fixtures\TypedProperties\BoolOrString;
use JMS\Serializer\Tests\Fixtures\TypedProperties\ComplexDiscriminatedUnion;
use JMS\Serializer\Tests\Fixtures\TypedProperties\FalseOrString;
use JMS\Serializer\Tests\Fixtures\TypedProperties\UnionTypedProperties;
use JMS\Serializer\Visitor\Factory\JsonSerializationVisitorFactory;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use TypeError;

class JsonSerializationTest extends BaseSerializationTestCase
{
    protected static function getContent($key)
    {
        static $outputs = [];

        if (!$outputs) {
            $outputs['nullable_root'] = 'null';
            $outputs['readonly'] = '{"id":123,"full_name":"Ruud Kamphuis"}';
            $outputs['string'] = '"foo"';
            $outputs['boolean_true'] = 'true';
            $outputs['boolean_false'] = 'false';
            $outputs['integer'] = '1';
            $outputs['float'] = '4.533';
            $outputs['float_trailing_zero'] = '1.0';
            $outputs['simple_object'] = '{"foo":"foo","moo":"bar","camel_case":"boo"}';
            $outputs['circular_reference'] = '{"collection":[{"name":"child1"},{"name":"child2"}],"another_collection":[{"name":"child1"},{"name":"child2"}]}';
            $outputs['circular_reference_collection'] = '{"name":"foo","collection":[]}';
            $outputs['array_strings'] = '["foo","bar"]';
            $outputs['array_booleans'] = '[true,false]';
            $outputs['array_integers'] = '[1,3,4]';
            $outputs['array_empty'] = '{"array":[]}';
            $outputs['array_floats'] = '[1.34,3.0,6.42]';
            $outputs['array_objects'] = '[{"foo":"foo","moo":"bar","camel_case":"boo"},{"foo":"baz","moo":"boo","camel_case":"boo"}]';
            $outputs['array_list_and_map_difference'] = '{"list":[1,2,3],"map":{"0":1,"2":2,"3":3}}';
            $outputs['array_mixed'] = '["foo",1,true,{"foo":"foo","moo":"bar","camel_case":"boo"},[1,3,true]]';
            $outputs['array_datetimes_object'] = '{"array_with_default_date_time":["2047-01-01T12:47:47+00:00","2016-12-05T00:00:00+00:00"],"array_with_formatted_date_time":["01.01.2047 12:47:47","05.12.2016 00:00:00"]}';
            $outputs['array_named_datetimes_object'] = '{"named_array_with_formatted_date":{"testdate1":"01.01.2047 12:47:47","testdate2":"05.12.2016 00:00:00"}}';
            $outputs['array_datetimes_object'] = '{"array_with_default_date_time":["2047-01-01T12:47:47+00:00","2016-12-05T00:00:00+00:00"],"array_with_formatted_date_time":["01.01.2047 12:47:47","05.12.2016 00:00:00"]}';
            $outputs['array_named_datetimes_object'] = '{"named_array_with_formatted_date":{"testdate1":"01.01.2047 12:47:47","testdate2":"05.12.2016 00:00:00"}}';
            $outputs['array_named_datetimeimmutables_object'] = '{"named_array_with_formatted_date":{"testdate1":"01.01.2047 12:47:47","testdate2":"05.12.2016 00:00:00"}}';
            $outputs['blog_post'] = '{"id":"what_a_nice_id","title":"This is a nice title.","created_at":"2011-07-30T00:00:00+00:00","is_published":false,"is_reviewed":false,"etag":"e86ce85cdb1253e4fc6352f5cf297248bceec62b","comments":[{"author":{"full_name":"Foo Bar"},"text":"foo"}],"comments2":[{"author":{"full_name":"Foo Bar"},"text":"foo"}],"metadata":{"foo":"bar"},"author":{"full_name":"Foo Bar"},"publisher":{"pub_name":"Bar Foo"},"tag":[{"name":"tag1"},{"name":"tag2"}]}';
            $outputs['blog_post_unauthored'] = '{"id":"what_a_nice_id","title":"This is a nice title.","created_at":"2011-07-30T00:00:00+00:00","is_published":false,"is_reviewed":false,"etag":"e86ce85cdb1253e4fc6352f5cf297248bceec62b","comments":[],"comments2":[],"metadata":{"foo":"bar"},"author":null,"publisher":null,"tag":null}';
            $outputs['price'] = '{"price":3.0}';
            $outputs['currency_aware_price'] = '{"currency":"EUR","amount":2.34}';
            $outputs['order'] = '{"cost":{"price":12.34}}';
            $outputs['order_with_currency_aware_price'] = '{"cost":{"currency":"EUR","amount":1.23}}';
            $outputs['log'] = '{"author_list":[{"full_name":"Johannes Schmitt"},{"full_name":"John Doe"}],"comments":[{"author":{"full_name":"Foo Bar"},"text":"foo"},{"author":{"full_name":"Foo Bar"},"text":"bar"},{"author":{"full_name":"Foo Bar"},"text":"baz"}]}';
            $outputs['lifecycle_callbacks'] = '{"name":"Foo Bar"}';
            $outputs['list'] = '[1,3,4]';
            $outputs['list_empty'] = '[]';
            $outputs['list_integers'] = '[1,3,4]';
            $outputs['form_errors'] = '["This is the form error","Another error"]';
            $outputs['nested_form_errors'] = '{"errors":["This is the form error"],"children":{"bar":{"errors":["Error of the child form"]}}}';
            $outputs['constraint_violation'] = '{"property_path":"foo","message":"Message of violation"}';
            $outputs['constraint_violation_list'] = '[{"property_path":"foo","message":"Message of violation"},{"property_path":"bar","message":"Message of another violation"}]';
            $outputs['article'] = '{"custom":"serialized"}';
            $outputs['orm_proxy'] = '{"foo":"foo","moo":"bar","camel_case":"proxy-boo"}';
            $outputs['custom_accessor'] = '{"comments":{"Foo":{"comments":[{"author":{"full_name":"Foo"},"text":"foo"},{"author":{"full_name":"Foo"},"text":"bar"}],"count":2}}}';
            $outputs['mixed_access_types'] = '{"id":1,"name":"Johannes","read_only_property":42}';
            $outputs['accessor_order_child'] = '{"c":"c","d":"d","a":"a","b":"b"}';
            $outputs['accessor_order_parent'] = '{"a":"a","b":"b"}';
            $outputs['accessor_order_methods'] = '{"foo":"c","b":"b","a":"a"}';
            $outputs['inline'] = '{"c":"c","a":"a","b":"b","d":"d"}';
            $outputs['inline_child_empty'] = '{"c":"c","d":"d"}';
            $outputs['empty_child'] = '{"c":"c","d":"d","child":{}}';
            $outputs['empty_child_skip'] = '{"c":"c","d":"d"}';
            $outputs['groups_all'] = '{"foo":"foo","foobar":"foobar","bar":"bar","none":"none"}';
            $outputs['groups_foo'] = '{"foo":"foo","foobar":"foobar"}';
            $outputs['groups_foobar'] = '{"foo":"foo","foobar":"foobar","bar":"bar"}';
            $outputs['groups_default'] = '{"bar":"bar","none":"none"}';
            $outputs['groups_advanced'] = '{"name":"John","manager":{"name":"John Manager","friends":[{"nickname":"nickname"},{"nickname":"nickname"}]},"friends":[{"nickname":"nickname","manager":{"name":"John friend 1 manager"}},{"nickname":"nickname","manager":{"name":"John friend 2 manager"}}]}';
            $outputs['virtual_properties'] = '{"exist_field":"value","virtual_value":"value","test":"other-name","typed_virtual_property":1}';
            $outputs['virtual_properties_low'] = '{"classlow":1,"low":1}';
            $outputs['virtual_properties_high'] = '{"classhigh":8,"high":8}';
            $outputs['virtual_properties_all'] = '{"classlow":1,"classhigh":8,"low":1,"high":8}';
            $outputs['nullable'] = '{"foo":"bar","baz":null,"0":null}';
            $outputs['nullable_skip'] = '{"foo":"bar"}';
            $outputs['person_secret_show'] = '{"name":"mike","gender":"f"}';
            $outputs['person_secret_hide'] = '{"name":"mike"}';
            $outputs['null'] = 'null';
            $outputs['simple_object_nullable'] = '{"foo":"foo","moo":"bar","camel_case":"boo","null_property":null}';
            $outputs['input'] = '{"attributes":{"type":"text","name":"firstname","value":"Adrien"}}';
            $outputs['hash_empty'] = '{"hash":{}}';
            $outputs['object_when_null'] = '{"text":"foo"}';
            $outputs['object_when_null_and_serialized'] = '{"author":null,"text":"foo"}';
            $outputs['date_time'] = '"2011-08-30T00:00:00+00:00"';
            $outputs['date_time_immutable'] = '"2011-08-30T00:00:00+00:00"';
            $outputs['date_time_multi_format'] = '"2011-08-30T00:00:00+00:00"';
            $outputs['timestamp'] = '{"timestamp":1455148800}';
            $outputs['timestamp_prev'] = '{"timestamp":"1455148800"}';
            $outputs['date_interval'] = '"PT45M"';
            $outputs['car'] = '{"km":5,"type":"car"}';
            $outputs['car_without_type'] = '{"km":5}';
            $outputs['post'] = '{"type":"post","title":"Post Title"}';
            $outputs['image_post'] = '{"type":"image_post","title":"Image Post Title"}';
            $outputs['image_post_without_type'] = '{"title":"Image Post Title"}';
            $outputs['garage'] = '{"vehicles":[{"km":3,"type":"car"},{"km":1,"type":"moped"}]}';
            $outputs['tree'] = '{"tree":{"children":[{"children":[{"children":[],"foo":"bar"}],"foo":"bar"}],"foo":"bar"}}';
            $outputs['nullable_arrays'] = '{"empty_inline":[],"not_empty_inline":["not_empty_inline"],"empty_not_inline":[],"not_empty_not_inline":["not_empty_not_inline"],"empty_not_inline_skip":[],"not_empty_not_inline_skip":["not_empty_not_inline_skip"]}';
            $outputs['object_with_object_property_no_array_to_author'] = '{"foo": "bar", "author": "baz"}';
            $outputs['object_with_object_property'] = '{"foo": "bar", "author": {"full_name": "baz"}}';
            $outputs['author_expression'] = '{"my_first_name":"Ruud","last_name":"Kamphuis","id":123}';
            $outputs['author_expression_context'] = '{"first_name":"Ruud","direction":1,"name":"name"}';
            $outputs['maxdepth_skippabe_object'] = '{"a":{"xxx":"yyy"}}';
            $outputs['maxdepth_0'] = '{"a":{}}';
            $outputs['maxdepth_1'] = '{"a":{"b":12345}}';
            $outputs['array_objects_nullable'] = '[]';
            $outputs['type_casting'] = '{"as_string":"8"}';
            $outputs['authors_inline'] = '[{"full_name":"foo"},{"full_name":"bar"}]';
            $outputs['inline_list_collection'] = '[1,2,3]';
            $outputs['inline_empty_list_collection'] = '[]';
            $outputs['inline_deserialization_list_collection'] = '[1,2]';
            $outputs['inline_map'] = '{"a":"1","b":"2","c":"3"}';
            $outputs['inline_empty_map'] = '{}';
            $outputs['empty_object'] = '{}';
            $outputs['inline_deserialization_map'] = '{"a":"b","c":"d","e":"5"}';
            $outputs['iterable'] = '{"iterable":{"foo":"bar","bar":"foo"}}';
            $outputs['iterator'] = '{"iterator":{"foo":"bar","bar":"foo"}}';
            $outputs['array_iterator'] = '{"iterator":{"foo":"bar","bar":"foo"}}';
            $outputs['generator'] = '{"generator":{"foo":"bar","bar":"foo"}}';
            $outputs['ParentNoMetadataChildObject'] = '{"bar":"John"}';
            $outputs['user_discriminator_array'] = '[{"entityName":"User"},{"entityName":"ExtendedUser"}]';
            $outputs['user_discriminator'] = '{"entityName":"User"}';
            $outputs['user_discriminator_extended'] = '{"entityName":"ExtendedUser"}';
            $outputs['typed_props'] = '{"virtual_role":{"id":5},"id":1,"role":{"id":5},"vehicle":{"type":"car"},"created":"2010-10-01T00:00:00+00:00","updated":"2011-10-01T00:00:00+00:00","tags":["a","b"]}';
            $outputs['typed_props_constructor_promotion_with_default_values'] = '{"color":"blue","size":"big","type_of_soil":"potting mix","days_since_potting":-1,"weight":10}';
            $outputs['uninitialized_typed_props'] = '{"virtual_role":{},"id":1,"role":{},"tags":[]}';
            $outputs['custom_datetimeinterface'] = '{"custom":"2021-09-07"}';
            $outputs['data_integer'] = '{"data":10000}';
            $outputs['data_integer_one'] = '{"data":1}';
            $outputs['data_float'] = '{"data":1.236}';
            $outputs['data_bool'] = '{"data":false}';
            $outputs['data_string'] = '{"data":"foo"}';
            $outputs['data_string_empty'] = '{"data":""}';
            $outputs['data_string_zero'] = '{"data":"0"}';
            $outputs['data_array'] = '{"data":[1,2,3]}';
            $outputs['data_true'] = '{"data":true}';
            $outputs['data_false'] = '{"data":false}';
            $outputs['data_author'] = '{"data":{"full_name":"foo"}}';
            $outputs['data_comment'] = '{"data":{"author":{"full_name":"foo"},"text":"bar"}}';
            $outputs['data_discriminated_author'] = '{"data":{"full_name":"foo","objectType":"author"}}';
            $outputs['data_discriminated_comment'] = '{"data":{"author":{"full_name":"foo"},"text":"bar","objectType":"comment"}}';
            $outputs['data_discriminated_comment_wrong_discriminator'] = '{"data":{"author":{"full_name":"foo"},"text":"bar","objectType":"comment_wrong"}}';
            $outputs['data_discriminated_comment_missing_discriminator'] = '{"data":{"author":{"full_name":"foo"},"text":"bar"}}';
            $outputs['uid'] = '"66b3177c-e03b-4a22-9dee-ddd7d37a04d5"';
            $outputs['object_with_enums'] = '{"ordinary":"Clubs","backed_value":"C","backed_without_param":"C","ordinary_array":["Clubs","Spades"],"backed_array":["C","H"],"backed_array_without_param":["C","H"],"ordinary_auto_detect":"Clubs","backed_auto_detect":"C","backed_int_auto_detect":3,"backed_int":3,"backed_name":"C","backed_int_forced_str":3}';
            $outputs['object_with_autodetect_enums'] = '{"ordinary_array_auto_detect":["Clubs","Spades"],"backed_array_auto_detect":["C","H"],"mixed_array_auto_detect":["Clubs","H"]}';
            $outputs['object_with_enums_disabled'] = '{"ordinary_array_auto_detect":[{"name":"Clubs"},{"name":"Spades"}],"backed_array_auto_detect":[{"name":"Clubs","value":"C"},{"name":"Hearts","value":"H"}],"mixed_array_auto_detect":[{"name":"Clubs"},{"name":"Hearts","value":"H"}]}';
            $outputs['union_typed_properties_integer'] = '{"data":10000,"value_typed_union":false}';
            $outputs['union_typed_properties_float'] = '{"data":1.236,"value_typed_union":false}';
            $outputs['union_typed_properties_bool'] = '{"data":false,"value_typed_union":false}';
            $outputs['union_typed_properties_string'] = '{"data":"foo","value_typed_union":false}';
            $outputs['union_typed_properties_array'] = '{"data":[1,2,3],"value_typed_union":false}';
            $outputs['union_typed_properties_false_string'] = '{"data":false,"value_typed_union":"foo"}';
        }

        if (!isset($outputs[$key])) {
            throw new RuntimeException(sprintf('The key "%s" is not supported.', $key));
        }

        return $outputs[$key];
    }

    public function testSkipEmptyArrayAndHash()
    {
        $object = new ObjectWithEmptyArrayAndHash();

        self::assertEquals('{}', $this->serialize($object));
    }

    public static function getFirstClassMapCollectionsValues()
    {
        return [
            [['a' => '1', 'b' => '2', 'c' => '3'], self::getContent('inline_map')],
            [[], self::getContent('inline_empty_map')],
            [['a' => 'b', 'c' => 'd', 'e' => '5'], self::getContent('inline_deserialization_map')],
        ];
    }

    /**
     * @dataProvider getFirstClassMapCollectionsValues
     */
    #[DataProvider('getFirstClassMapCollectionsValues')]
    public function testFirstClassMapCollections(array $items, string $expected): void
    {
        $collection = new FirstClassMapCollection($items);

        self::assertSame($expected, $this->serialize($collection));
        self::assertEquals(
            $collection,
            $this->deserialize($expected, get_class($collection)),
        );
    }

    public function testAddLinksToOutput()
    {
        $this->dispatcher->addListener('serializer.post_serialize', static function (Event $event) {
            self::assertFalse($event->getVisitor()->hasData('_links'));
        }, Author::class, 'json');

        $this->dispatcher->addSubscriber(new LinkAddingSubscriber());

        $this->dispatcher->addListener('serializer.post_serialize', static function (Event $event) {
            self::assertTrue($event->getVisitor()->hasData('_links'));
        }, Author::class, 'json');

        $this->handlerRegistry->registerHandler(
            GraphNavigatorInterface::DIRECTION_SERIALIZATION,
            AuthorList::class,
            'json',
            static function (SerializationVisitorInterface $visitor, AuthorList $data, array $type, Context $context) {
                return $visitor->visitArray(iterator_to_array($data), $type);
            },
        );

        $list = new AuthorList();
        $list->add(new Author('foo'));
        $list->add(new Author('bar'));

        self::assertEquals('[{"full_name":"foo","_links":{"details":"http:\/\/foo.bar\/details\/foo","comments":"http:\/\/foo.bar\/details\/foo\/comments"}},{"full_name":"bar","_links":{"details":"http:\/\/foo.bar\/details\/bar","comments":"http:\/\/foo.bar\/details\/bar\/comments"}}]', $this->serialize($list));
    }

    public function testReplaceNameInOutput()
    {
        $this->dispatcher->addSubscriber(new ReplaceNameSubscriber());
        $this->handlerRegistry->registerHandler(
            GraphNavigatorInterface::DIRECTION_SERIALIZATION,
            AuthorList::class,
            'json',
            static function (SerializationVisitorInterface $visitor, AuthorList $data, array $type, Context $context) {
                return $visitor->visitArray(iterator_to_array($data), $type);
            },
        );

        $list = new AuthorList();
        $list->add(new Author('foo'));
        $list->add(new Author('bar'));

        self::assertEquals('[{"full_name":"new name"},{"full_name":"new name"}]', $this->serialize($list));
    }

    public function testDeserializingObjectWithObjectPropertyWithNoArrayToObject()
    {
        $content = self::getContent('object_with_object_property_no_array_to_author');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid data "baz" (string), expected "JMS\Serializer\Tests\Fixtures\Author".');

        $this->deserialize($content, ObjectWithObjectProperty::class);
    }

    public function testDeserializingObjectWithObjectProperty()
    {
        $content = self::getContent('object_with_object_property');
        $object = $this->deserialize($content, ObjectWithObjectProperty::class);
        self::assertEquals('bar', $object->getFoo());
        self::assertInstanceOf(Author::class, $object->getAuthor());
        self::assertEquals('baz', $object->getAuthor()->getName());
    }

    public static function getPrimitiveTypes()
    {
        return [
            [
                'type' => 'boolean',
                'data' => true,
            ],
            [
                'type' => 'integer',
                'data' => 123,
            ],
            [
                'type' => 'string',
                'data' => 'hello',
            ],
            [
                'type' => 'double',
                'data' => 0.1234,
            ],
        ];
    }

    /**
     * @dataProvider getPrimitiveTypes
     */
    #[DataProvider('getPrimitiveTypes')]
    public function testPrimitiveTypes(string $type, $data)
    {
        $navigator = $this->getMockBuilder(GraphNavigatorInterface::class)->getMock();

        $factory = new JsonSerializationVisitorFactory();
        $visitor = $factory->getVisitor();
        $visitor->setNavigator($navigator);
        $functionToCall = 'visit' . ucfirst($type);
        $result = $visitor->$functionToCall($data, [], $this->getMockBuilder(SerializationContext::class)->getMock());
        self::{'assertIs' . (['boolean' => 'bool', 'integer' => 'int', 'double' => 'float'][$type] ?? $type)}($result);
    }

    public function testSerializeEmptyObject()
    {
        self::assertEquals('{}', $this->serialize(new Author(null)));
    }

    public function testSerializeWithNonUtf8EncodingWhenDisplayErrorsOff()
    {
        ini_set('display_errors', '1');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Your data could not be encoded because it contains invalid UTF8 characters.');

        $this->serialize(['foo' => 'bar', 'bar' => pack('H*', 'c32e')]);
    }

    public function testSerializeWithNonUtf8EncodingWhenDisplayErrorsOn()
    {
        ini_set('display_errors', '0');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Your data could not be encoded because it contains invalid UTF8 characters.');

        $this->serialize(['foo' => 'bar', 'bar' => pack('H*', 'c32e')]);
    }

    public function testSerializeArrayWithEmptyObject()
    {
        self::assertEquals('[{}]', $this->serialize([new \stdClass()]));
    }

    public function testInlineArray()
    {
        $object = new ObjectWithInlineArray(['a' => 'b', 'c' => 'd']);
        $serialized = $this->serialize($object);
        self::assertEquals('{"a":"b","c":"d"}', $serialized);
        self::assertEquals($object, $this->deserialize($serialized, ObjectWithInlineArray::class));
    }

    public function testSerializeRootArrayWithDefinedKeys()
    {
        $author1 = new Author('Jim');
        $author2 = new Author('Mark');

        $data = [
            'jim' => $author1,
            'mark' => $author2,
        ];

        self::assertEquals('{"jim":{"full_name":"Jim"},"mark":{"full_name":"Mark"}}', $this->serializer->serialize($data, $this->getFormat(), SerializationContext::create()->setInitialType('array')));
        self::assertEquals('[{"full_name":"Jim"},{"full_name":"Mark"}]', $this->serializer->serialize($data, $this->getFormat(), SerializationContext::create()->setInitialType('array<JMS\Serializer\Tests\Fixtures\Author>')));
        self::assertEquals('{"jim":{"full_name":"Jim"},"mark":{"full_name":"Mark"}}', $this->serializer->serialize($data, $this->getFormat(), SerializationContext::create()->setInitialType('array<string,JMS\Serializer\Tests\Fixtures\Author>')));

        $data = [
            $author1,
            $author2,
        ];
        self::assertEquals('[{"full_name":"Jim"},{"full_name":"Mark"}]', $this->serializer->serialize($data, $this->getFormat(), SerializationContext::create()->setInitialType('array')));
        self::assertEquals('{"0":{"full_name":"Jim"},"1":{"full_name":"Mark"}}', $this->serializer->serialize($data, $this->getFormat(), SerializationContext::create()->setInitialType('array<int,JMS\Serializer\Tests\Fixtures\Author>')));
        self::assertEquals('{"0":{"full_name":"Jim"},"1":{"full_name":"Mark"}}', $this->serializer->serialize($data, $this->getFormat(), SerializationContext::create()->setInitialType('array<string,JMS\Serializer\Tests\Fixtures\Author>')));
    }

    public static function getTypeHintedArrays()
    {
        return [

            [[1, 2], '[1,2]', null],
            [['a', 'b'], '["a","b"]', null],
            [['a' => 'a', 'b' => 'b'], '{"a":"a","b":"b"}', null],

            [[], '[]', null],
            [[], '[]', SerializationContext::create()->setInitialType('array')],
            [[], '[]', SerializationContext::create()->setInitialType('array<integer>')],
            [[], '{}', SerializationContext::create()->setInitialType('array<string,integer>')],

            [[1, 2], '[1,2]', SerializationContext::create()->setInitialType('array')],
            [[1 => 1, 2 => 2], '{"1":1,"2":2}', SerializationContext::create()->setInitialType('array')],
            [[1 => 1, 2 => 2], '[1,2]', SerializationContext::create()->setInitialType('array<integer>')],
            [['a', 'b'], '["a","b"]', SerializationContext::create()->setInitialType('array<string>')],
            [['a', 'b'], '["a","b"]', SerializationContext::create()->setInitialType('list<string>')],

            [[1 => 'a', 2 => 'b'], '["a","b"]', SerializationContext::create()->setInitialType('array<string>')],
            [['a' => 'a', 'b' => 'b'], '["a","b"]', SerializationContext::create()->setInitialType('array<string>')],

            [[1, 2], '{"0":1,"1":2}', SerializationContext::create()->setInitialType('array<integer,integer>')],
            [[1, 2], '{"0":1,"1":2}', SerializationContext::create()->setInitialType('array<string,integer>')],
            [[1, 2], '{"0":"1","1":"2"}', SerializationContext::create()->setInitialType('array<string,string>')],

            [['a', 'b'], '{"0":"a","1":"b"}', SerializationContext::create()->setInitialType('array<integer,string>')],
            [['a' => 'a', 'b' => 'b'], '{"a":"a","b":"b"}', SerializationContext::create()->setInitialType('array<string,string>')],

            [[15.6, 2], '[15.6,2.0]', SerializationContext::create()->setInitialType('array<float>')],
            [[5.2 * 3, 2], '[15.6,2.0]', SerializationContext::create()->setInitialType('array<float>')],
        ];
    }

    /**
     * @dataProvider getTypeHintedArrays
     */
    #[DataProvider('getTypeHintedArrays')]
    public function testTypeHintedArraySerialization(array $array, string $expected, ?SerializationContext $context = null)
    {
        self::assertEquals($expected, $this->serialize($array, $context));
    }

    public static function getTypeHintedArraysAndStdClass()
    {
        $c1 = new \stdClass();
        $c2 = new \stdClass();
        $c2->foo = 'bar';

        $tag = new Tag('tag');

        $c3 = new \stdClass();
        $c3->foo = $tag;

        return [

            [[$c1], '[{}]', SerializationContext::create()->setInitialType('array<stdClass>')],

            [[$c2], '[{"foo":"bar"}]', SerializationContext::create()->setInitialType('array<stdClass>')],

            [[$tag], '[{"name":"tag"}]', SerializationContext::create()->setInitialType('array<JMS\Serializer\Tests\Fixtures\Tag>')],

            [[$c1], '{"0":{}}', SerializationContext::create()->setInitialType('array<integer,stdClass>')],
            [[$c2], '{"0":{"foo":"bar"}}', SerializationContext::create()->setInitialType('array<integer,stdClass>')],

            [[$c3], '{"0":{"foo":{"name":"tag"}}}', SerializationContext::create()->setInitialType('array<integer,stdClass>')],
            [[$c3], '[{"foo":{"name":"tag"}}]', SerializationContext::create()->setInitialType('array<stdClass>')],

            [[$tag], '{"0":{"name":"tag"}}', SerializationContext::create()->setInitialType('array<integer,JMS\Serializer\Tests\Fixtures\Tag>')],
        ];
    }

    public static function getSimpleUnionProperties(): iterable
    {
        yield [10000, 'data_integer'];
        yield [1.236, 'data_float'];
        yield [false, 'data_bool'];
        yield ['foo', 'data_string'];
        yield [[1, 2, 3], 'data_array'];
        yield [1, 'data_integer_one'];
        yield ['0', 'data_string_zero'];
        yield ['', 'data_string_empty'];
        yield [true, 'data_true'];
        yield [false, 'data_false'];
    }

    /**
     * @dataProvider getSimpleUnionProperties
     */
    #[DataProvider('getSimpleUnionProperties')]
    public function testUnionProperties($data, string $expected): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped(sprintf('%s requires PHP 8.0', TypedPropertiesDriver::class));

            return;
        }

        $deserialized = $this->deserialize(static::getContent($expected), UnionTypedProperties::class);

        self::assertSame($data, $deserialized->data);
        self::assertSame($this->serialize($deserialized), static::getContent($expected));
    }

    public static function getUnionCastableTypes(): iterable
    {
        yield ['10000', 'data_integer'];
        yield ['1.236', 'data_float'];
        yield [true, 'data_integer_one'];
    }

    /**
     * @dataProvider getUnionCastableTypes
     */
    #[DataProvider('getUnionCastableTypes')]
    public function testUnionPropertiesWithCastableType($data, string $expected)
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped(sprintf('%s requires PHP 8.0', TypedPropertiesDriver::class));

            return;
        }

        $deserialized = $this->deserialize(static::getContent($expected), BoolOrString::class);

        self::assertSame($data, $deserialized->data);
    }

    public static function getUnionNotCastableTypes(): iterable
    {
        yield ['data_array'];
    }

    /**
     * @dataProvider getUnionNotCastableTypes
     */
    #[DataProvider('getUnionNotCastableTypes')]
    public function testUnionPropertiesWithNotCastableType(string $expected)
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped(sprintf('%s requires PHP 8.0', TypedPropertiesDriver::class));

            return;
        }

        $deserialized = $this->deserialize(static::getContent($expected), BoolOrString::class);

        $this->expectException(\Error::class);
        $deserialized->data;
    }

    public function testTrueDataType()
    {
        if (PHP_VERSION_ID < 80200) {
            $this->markTestSkipped('True type requires PHP 8.2');

            return;
        }

        self::assertEquals(
            static::getContent('data_true'),
            $this->serialize(new DataTrue(true)),
        );
        self::assertEquals(
            new DataTrue(true),
            $this->deserialize(static::getContent('data_true'), DataTrue::class),
        );

        $this->expectException(TypeError::class);
        $this->deserialize(static::getContent('data_false'), DataTrue::class);
    }

    public function testFalseDataType()
    {
        if (PHP_VERSION_ID < 80200) {
            $this->markTestSkipped('False type requires PHP 8.2');

            return;
        }

        self::assertEquals(
            static::getContent('data_false'),
            $this->serialize(new DataFalse(false)),
        );

        self::assertEquals(
            new DataFalse(false),
            $this->deserialize(static::getContent('data_false'), DataFalse::class),
        );

        self::assertEquals(
            static::getContent('data_false'),
            $this->serialize(new FalseOrString(false)),
        );

        self::assertEquals(
            new FalseOrString(false),
            $this->deserialize(static::getContent('data_false'), FalseOrString::class),
        );

        $this->expectException(TypeError::class);
        $this->deserialize(static::getContent('data_true'), DataFalse::class);
    }

    public function testDeserializingComplexDiscriminatedUnionProperties()
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped(sprintf('%s requires PHP 8.0', TypedPropertiesDriver::class));

            return;
        }

        $authorUnion = new ComplexDiscriminatedUnion(new DiscriminatedAuthor('foo'));
        self::assertEquals($authorUnion, $this->deserialize(static::getContent('data_discriminated_author'), ComplexDiscriminatedUnion::class));

        $commentUnion = new ComplexDiscriminatedUnion(new DiscriminatedComment(new Author('foo'), 'bar'));

        self::assertEquals($commentUnion, $this->deserialize(static::getContent('data_discriminated_comment'), ComplexDiscriminatedUnion::class));
    }

    public function testDeserializingComplexDiscriminatedUnionPropertiesFailsWhenDiscriminatorNotInMap()
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped(sprintf('%s requires PHP 8.0', TypedPropertiesDriver::class));

            return;
        }

        $this->expectException(NonVisitableTypeException::class);
        $this->expectExceptionMessage('Union Discriminator Map does not contain key "comment_wrong"');
        $this->deserialize(static::getContent('data_discriminated_comment_wrong_discriminator'), ComplexDiscriminatedUnion::class);
    }

    public function testDeserializingComplexDiscriminatedUnionPropertiesFailsWhenDiscriminatorIsMissing()
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped(sprintf('%s requires PHP 8.0', TypedPropertiesDriver::class));

            return;
        }

        $this->expectException(NonVisitableTypeException::class);
        $this->expectExceptionMessage('Union Discriminator Field "objectType" not found in data');
        $this->deserialize(static::getContent('data_discriminated_comment_missing_discriminator'), ComplexDiscriminatedUnion::class);
    }

    public function testSerializeingComplexDiscriminatedUnionProperties()
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped(sprintf('%s requires PHP 8.0', TypedPropertiesDriver::class));

            return;
        }

        $serialized = $this->serialize(new ComplexDiscriminatedUnion(new DiscriminatedAuthor('foo')));
        self::assertEquals(static::getContent('data_discriminated_author'), $serialized);

        $serialized = $this->serialize(new ComplexDiscriminatedUnion(new DiscriminatedComment(new Author('foo'), 'bar')));
        self::assertEquals(static::getContent('data_discriminated_comment'), $serialized);
    }

    /**
     * @dataProvider getTypeHintedArraysAndStdClass
     */
    #[DataProvider('getTypeHintedArraysAndStdClass')]
    public function testTypeHintedArrayAncdtdClassSerialization(array $array, string $expected, ?SerializationContext $context = null)
    {
        self::assertEquals($expected, $this->serialize($array, $context));
    }

    protected function getFormat()
    {
        return 'json';
    }
}

class LinkAddingSubscriber implements EventSubscriberInterface
{
    public function onPostSerialize(ObjectEvent $event)
    {
        $author = $event->getObject();

        $event->getVisitor()->setData('_links', [
            'details' => 'http://foo.bar/details/' . $author->getName(),
            'comments' => 'http://foo.bar/details/' . $author->getName() . '/comments',
        ]);
    }

    public static function getSubscribedEvents()
    {
        return [
            ['event' => 'serializer.post_serialize', 'method' => 'onPostSerialize', 'format' => 'json', 'class' => Author::class],
        ];
    }
}

class ReplaceNameSubscriber implements EventSubscriberInterface
{
    public function onPostSerialize(Event $event)
    {
        $event->getVisitor()->setData('full_name', 'new name');
    }

    public static function getSubscribedEvents()
    {
        return [
            ['event' => 'serializer.post_serialize', 'method' => 'onPostSerialize', 'format' => 'json', 'class' => Author::class],
        ];
    }
}
