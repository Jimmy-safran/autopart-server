<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class InstanceofConfigurator extends AbstractServiceConfigurator
{
    use Traits\AutowireTrait;
    use Traits\BindTrait;
    use Traits\CallTrait;
    use Traits\ConfiguratorTrait;
    use Traits\ConstructorTrait;
    use Traits\LazyTrait;
    use Traits\PropertyTrait;
    use Traits\PublicTrait;
    use Traits\ShareTrait;
    use Traits\TagTrait;

    public const FACTORY = 'instanceof';

    public function __construct(
        ServicesConfigurator $parent,
        Definition $definition,
        string $id,
        private ?string $path = null,
    ) {
        parent::__construct($parent, $definition, $id, []);
    }

    /**
     * Defines an instanceof-conditional to be applied to following service definitions.
     */
    final public function instanceof(string $fqcn): self
    {
        return $this->parent->instanceof($fqcn);
    }
}
