<?php

namespace LaminasTest\Code\TestAsset;

include __DIR__ . '/foo/bar/baz.php';

use A\B\C;
use Foo\Bar\Baz as FooBarBaz;

abstract class BarClass
{
    public const BAR = 5;
    public const FOO = self::BAR;

    protected static $bar = 'value';

    final public function one()
    {
        // foo
    }

    protected function two(\ArrayObject $o = null)
    {
        // two
    }

    protected function three(\ArrayObject $o, &$t = 2, FooBarBaz\BazBarFoo $bbf = self::BAR)
    {
        $x = 5 + 5;
        $y = 'this string';
    }

    protected function four($one, $two = array([array('default')]))
    {
        // four
    }

    private function five(\LaminasTest\Code\TestAsset\AbstractClass $a)
    {
        // five
    }
}
