<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Serializer;

use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\Tests\Fixtures\Author;
use JMS\Serializer\Tests\Fixtures\AuthorList;
use JMS\Serializer\Tests\Fixtures\Order;
use JMS\Serializer\Tests\Fixtures\Price;
use JMS\Serializer\Tests\Fixtures\TypedProperties\ConstructorPromotion\DefaultValuesAndAccessors;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ArrayTest extends TestCase
{
    protected $serializer;

    protected function setUp(): void
    {
        $builder = SerializerBuilder::create();
        $this->serializer = $builder->build();
    }

    public function testToArray()
    {
        $order = new Order(new Price(5));

        $expected = [
            'cost' => ['price' => 5],
        ];

        $result = $this->serializer->toArray($order);

        self::assertEquals($expected, $result);
    }

    /**
     * @dataProvider scalarValues
     */
    #[DataProvider('scalarValues')]
    public function testToArrayWithScalar($input)
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'The input data of type "%s" did not convert to an array, but got a result of type "%s".',
            gettype($input),
            gettype($input),
        ));
        $result = $this->serializer->toArray($input);

        self::assertEquals([$input], $result);
    }

    public static function scalarValues()
    {
        return [
            [42],
            [3.14159],
            ['helloworld'],
            [true],
        ];
    }

    public function testFromArray()
    {
        $data = [
            'cost' => ['price' => 2.5],
        ];

        $expected = new Order(new Price(2.5));
        $result = $this->serializer->fromArray($data, Order::class);

        self::assertEquals($expected, $result);
    }

    public function testToArrayReturnsArrayObjectAsArray()
    {
        $result = $this->serializer->toArray(new Author(null));

        self::assertSame([], $result);
    }

    public function testToArrayConversNestedArrayObjects()
    {
        $list = new AuthorList();
        $list->add(new Author(null));

        $result = $this->serializer->toArray($list);
        self::assertSame(['authors' => [[]]], $result);
    }

    public function testConstructorPromotionWithDefaultValuesOnly()
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped(sprintf('%s requires PHP 8.0', __METHOD__));
        }

        $deserialized = $this->serializer->fromArray([], DefaultValuesAndAccessors::class);

        $expected = [
            'traditional_with_setter' => 'default',
            'traditional' => 'default',
            'promoted' => 'default',
            'promoted_with_setter' => 'default',
        ];
        $this->assertEquals($expected, $this->serializer->toArray($deserialized));
    }
}
