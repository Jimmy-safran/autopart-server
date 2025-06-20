<?php

namespace Doctrine\Tests\Common\Annotations\Fixtures;

use Doctrine\Tests\Common\Annotations\Fixtures\Annotation\Route;
use Doctrine\Tests\Common\Annotations\Fixtures\Traits\TraitThatUsesAnotherTrait;

/**
 * @Route("/someprefix")
 */
class ClassThatUsesTraitThatUsesAnotherTrait
{
    use TraitThatUsesAnotherTrait;
}
