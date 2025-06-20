<?php

namespace Doctrine\Tests\Common\Annotations\Fixtures;

use Doctrine\Tests\Common\Annotations\Fixtures\AnnotationWithTargetSyntaxError;

/**
 * @AnnotationWithTargetSyntaxError()
 */
class ClassWithAnnotationWithTargetSyntaxError
{
    /**
     * @var mixed
     * @AnnotationWithTargetSyntaxError()
     */
    public $foo;

    /**
     * @AnnotationWithTargetSyntaxError()
     */
    public function bar(): void
    {
    }
}
