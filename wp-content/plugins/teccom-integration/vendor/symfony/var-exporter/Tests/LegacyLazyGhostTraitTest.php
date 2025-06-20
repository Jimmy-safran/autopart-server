<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\VarExporter\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\VarExporter\ProxyHelper;
use Symfony\Component\VarExporter\Tests\Fixtures\LazyGhost\ChildMagicClass;
use Symfony\Component\VarExporter\Tests\Fixtures\LazyGhost\ChildStdClass;
use Symfony\Component\VarExporter\Tests\Fixtures\LazyGhost\ChildTestClass;
use Symfony\Component\VarExporter\Tests\Fixtures\LazyGhost\ClassWithUninitializedObjectProperty;
use Symfony\Component\VarExporter\Tests\Fixtures\LazyGhost\LazyClass;
use Symfony\Component\VarExporter\Tests\Fixtures\LazyGhost\MagicClass;
use Symfony\Component\VarExporter\Tests\Fixtures\LazyGhost\ReadOnlyClass;
use Symfony\Component\VarExporter\Tests\Fixtures\LazyGhost\TestClass;
use Symfony\Component\VarExporter\Tests\Fixtures\LazyProxy\AsymmetricVisibility;
use Symfony\Component\VarExporter\Tests\Fixtures\LazyProxy\Hooked;
use Symfony\Component\VarExporter\Tests\Fixtures\LazyProxy\HookedWithDefaultValue;
use Symfony\Component\VarExporter\Tests\Fixtures\SimpleObject;

/**
 * @group legacy
 */
class LegacyLazyGhostTraitTest extends TestCase
{
    public function testGetPublic()
    {
        $instance = ChildTestClass::createLazyGhost(function (ChildTestClass $ghost) {
            $ghost->__construct();
        });

        $this->assertSame(["\0".TestClass::class."\0lazyObjectState"], array_keys((array) $instance));
        $this->assertSame(-4, $instance->public);
        $this->assertSame(4, $instance->publicReadonly);
    }

    public function testGetPrivate()
    {
        $instance = ChildTestClass::createLazyGhost(function (ChildTestClass $ghost) {
            $ghost->__construct();
        });

        $r = new \ReflectionProperty(TestClass::class, 'private');

        $this->assertSame(-3, $r->getValue($instance));
    }

    public function testIssetPublic()
    {
        $instance = ChildTestClass::createLazyGhost(function (ChildTestClass $ghost) {
            $ghost->__construct();
        });

        $this->assertSame(["\0".TestClass::class."\0lazyObjectState"], array_keys((array) $instance));
        $this->assertTrue(isset($instance->public));
        $this->assertSame(4, $instance->publicReadonly);
    }

    public function testUnsetPublic()
    {
        $instance = ChildTestClass::createLazyGhost(function (ChildTestClass $ghost) {
            $ghost->__construct();
        });

        $this->assertSame(["\0".TestClass::class."\0lazyObjectState"], array_keys((array) $instance));
        unset($instance->public);
        $this->assertSame(4, $instance->publicReadonly);
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('__isset(public)');
        isset($instance->public);
    }

    public function testSetPublic()
    {
        $instance = ChildTestClass::createLazyGhost(function (ChildTestClass $ghost) {
            $ghost->__construct();
        });

        $this->assertSame(["\0".TestClass::class."\0lazyObjectState"], array_keys((array) $instance));
        $instance->public = 12;
        $this->assertSame(12, $instance->public);
        $this->assertSame(4, $instance->publicReadonly);
    }

    public function testSetPrivate()
    {
        $instance = ChildTestClass::createLazyGhost(function (ChildTestClass $ghost) {
            $ghost->__construct();
        });

        $r = new \ReflectionProperty(TestClass::class, 'private');
        $r->setValue($instance, 3);

        $this->assertSame(3, $r->getValue($instance));
    }

    public function testClone()
    {
        $instance = ChildTestClass::createLazyGhost(function (ChildTestClass $ghost) {
            $ghost->__construct();
        });

        $clone = clone $instance;

        $this->assertNotSame((array) $instance, (array) $clone);
        $this->assertSame(["\0".TestClass::class."\0lazyObjectState"], array_keys((array) $instance));
        $this->assertSame(["\0".TestClass::class."\0lazyObjectState"], array_keys((array) $clone));

        $clone = clone $clone;
        $this->assertTrue($clone->resetLazyObject());
    }

    public function testSerialize()
    {
        $instance = ChildTestClass::createLazyGhost(function (ChildTestClass $ghost) {
            $ghost->__construct();
        });

        $serialized = serialize($instance);
        $this->assertStringNotContainsString('lazyObjectState', $serialized);

        $clone = unserialize($serialized);
        $expected = (array) $instance;
        $this->assertArrayHasKey("\0".TestClass::class."\0lazyObjectState", $expected);
        unset($expected["\0".TestClass::class."\0lazyObjectState"]);
        $this->assertSame(array_keys($expected), array_keys((array) $clone));
        $this->assertFalse($clone->resetLazyObject());
        $this->assertTrue($clone->isLazyObjectInitialized());
    }

    /**
     * @dataProvider provideMagicClass
     */
    public function testMagicClass(MagicClass $instance)
    {
        $this->assertSame('bar', $instance->foo);
        $instance->foo = 123;
        $this->assertSame(123, $instance->foo);
        $this->assertTrue(isset($instance->foo));
        unset($instance->foo);
        $this->assertFalse(isset($instance->foo));

        $clone = clone $instance;
        $this->assertSame(0, $instance->cloneCounter);
        $this->assertSame(1, $clone->cloneCounter);

        $instance->bar = 123;
        $serialized = serialize($instance);
        $clone = unserialize($serialized);

        if ($instance instanceof ChildMagicClass) {
            // ChildMagicClass redefines the $data property but not the __sleep() method
            $this->assertFalse(isset($clone->bar));
        } else {
            $this->assertSame(123, $clone->bar);
        }
    }

    public static function provideMagicClass()
    {
        yield [new MagicClass()];

        yield [ChildMagicClass::createLazyGhost(function (ChildMagicClass $instance) {
            $instance->__construct();
        })];
    }

    public function testResetLazyGhost()
    {
        $instance = ChildMagicClass::createLazyGhost(function (ChildMagicClass $instance) {
            $instance->__construct();
        });

        $instance->foo = 234;
        $this->assertTrue($instance->resetLazyObject());
        $this->assertFalse($instance->isLazyObjectInitialized());
        $this->assertSame('bar', $instance->foo);
    }

    public function testFullInitialization()
    {
        $counter = 0;
        $instance = ChildTestClass::createLazyGhost(function (ChildTestClass $ghost) use (&$counter) {
            ++$counter;
            $ghost->__construct();
        });

        $this->assertFalse($instance->isLazyObjectInitialized());
        $this->assertTrue(isset($instance->public));
        $this->assertTrue($instance->isLazyObjectInitialized());
        $this->assertSame(-4, $instance->public);
        $this->assertSame(4, $instance->publicReadonly);
        $this->assertSame(1, $counter);
    }

    public function testSetStdClassProperty()
    {
        $instance = ChildStdClass::createLazyGhost(function (ChildStdClass $ghost) {
        });

        $instance->public = 12;
        $this->assertSame(12, $instance->public);
    }

    public function testLazyClass()
    {
        $obj = new LazyClass(fn ($proxy) => $proxy->public = 123);

        $this->assertSame(123, $obj->public);
    }

    public function testReflectionPropertyGetValue()
    {
        $obj = TestClass::createLazyGhost(fn ($proxy) => $proxy->__construct());

        $r = new \ReflectionProperty($obj, 'private');

        $this->assertSame(-3, $r->getValue($obj));
    }

    public function testIndirectModification()
    {
        $obj = new class {
            public array $foo;
        };
        $proxy = $this->createLazyGhost($obj::class, fn () => null);

        $proxy->foo[] = 123;

        $this->assertSame([123], $proxy->foo);
    }

    /**
     * @requires PHP 8.3
     */
    public function testReadOnlyClass()
    {
        $proxy = $this->createLazyGhost(ReadOnlyClass::class, fn ($proxy) => $proxy->__construct(123));

        $this->assertSame(123, $proxy->foo);
    }

    public function testAccessingUninializedPropertyWithoutLazyGhost()
    {
        $object = new ClassWithUninitializedObjectProperty();

        $this->expectException(\Error::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Typed property Symfony\Component\VarExporter\Tests\Fixtures\LazyGhost\ClassWithUninitializedObjectProperty::$property must not be accessed before initialization');

        $object->property;
    }

    public function testAccessingUninializedPropertyWithLazyGhost()
    {
        $object = $this->createLazyGhost(ClassWithUninitializedObjectProperty::class, function ($instance) {});

        $this->expectException(\Error::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Typed property Symfony\Component\VarExporter\Tests\Fixtures\LazyGhost\ClassWithUninitializedObjectProperty::$property must not be accessed before initialization');

        $object->property;
    }

    public function testNormalization()
    {
        $object = $this->createLazyGhost(SimpleObject::class, function ($instance) {});

        $loader = new AttributeLoader();
        $metadataFactory = new ClassMetadataFactory($loader);
        $serializer = new ObjectNormalizer($metadataFactory);

        $output = $serializer->normalize($object);

        $this->assertSame(['property' => 'property', 'method' => 'method'], $output);
    }

    public function testReinitLazyGhost()
    {
        $object = TestClass::createLazyGhost(function ($p) { $p->public = 2; });

        $this->assertSame(2, $object->public);

        TestClass::createLazyGhost(function ($p) { $p->public = 3; }, null, $object);

        $this->assertSame(3, $object->public);
    }

    /**
     * @requires PHP 8.4
     */
    public function testPropertyHooks()
    {
        $initialized = false;
        $object = $this->createLazyGhost(Hooked::class, function ($instance) use (&$initialized) {
            $initialized = true;
        });

        $this->assertSame(123, $object->notBacked);
        $this->assertFalse($initialized);
        $this->assertSame(234, $object->backed);
        $this->assertTrue($initialized);

        $initialized = false;
        $object = $this->createLazyGhost(Hooked::class, function ($instance) use (&$initialized) {
            $initialized = true;
        });

        $object->backed = 345;
        $this->assertTrue($initialized);
        $this->assertSame(345, $object->backed);
    }

    /**
     * @requires PHP 8.4
     */
    public function testPropertyHooksWithDefaultValue()
    {
        $initialized = false;
        $object = $this->createLazyGhost(HookedWithDefaultValue::class, function ($instance) use (&$initialized) {
            $initialized = true;
        });

        $this->assertSame(321, $object->backedIntWithDefault);
        $this->assertSame('321', $object->backedStringWithDefault);
        $this->assertSame(false, $object->backedBoolWithDefault);
        $this->assertTrue($initialized);

        $initialized = false;
        $object = $this->createLazyGhost(HookedWithDefaultValue::class, function ($instance) use (&$initialized) {
            $initialized = true;
        });
        $object->backedIntWithDefault = 654;
        $object->backedStringWithDefault = '654';
        $object->backedBoolWithDefault = true;
        $this->assertTrue($initialized);
        $this->assertSame(654, $object->backedIntWithDefault);
        $this->assertSame('654', $object->backedStringWithDefault);
        $this->assertSame(true, $object->backedBoolWithDefault);
    }

    /**
     * @requires PHP 8.4
     */
    public function testAsymmetricVisibility()
    {
        $object = $this->createLazyGhost(AsymmetricVisibility::class, function ($instance) {
            $instance->__construct(123, 234);
        });

        $this->assertSame(123, $object->foo);
        $this->assertSame(234, $object->getBar());

        $object = $this->createLazyGhost(AsymmetricVisibility::class, function ($instance) {
            $instance->__construct(123, 234);
        });

        $this->assertSame(234, $object->getBar());
        $this->assertSame(123, $object->foo);
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function createLazyGhost(string $class, \Closure $initializer, ?array $skippedProperties = null): object
    {
        $r = new \ReflectionClass($class);

        if (str_contains($class, "\0")) {
            $class = __CLASS__.'\\'.debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'].'_L'.$r->getStartLine();
            class_alias($r->name, $class);
        }
        $proxy = str_replace($r->name, $class, ProxyHelper::generateLazyGhost($r));
        $class = str_replace('\\', '_', $class).'_'.md5($proxy);

        if (!class_exists($class, false)) {
            eval(($r->isReadOnly() ? 'readonly ' : '').'class '.$class.' '.$proxy);
        }

        return $class::createLazyGhost($initializer, $skippedProperties);
    }
}
