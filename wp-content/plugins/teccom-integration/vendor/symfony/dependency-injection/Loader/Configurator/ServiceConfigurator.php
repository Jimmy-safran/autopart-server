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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class ServiceConfigurator extends AbstractServiceConfigurator
{
    use Traits\AbstractTrait;
    use Traits\ArgumentTrait;
    use Traits\AutoconfigureTrait;
    use Traits\AutowireTrait;
    use Traits\BindTrait;
    use Traits\CallTrait;
    use Traits\ClassTrait;
    use Traits\ConfiguratorTrait;
    use Traits\ConstructorTrait;
    use Traits\DecorateTrait;
    use Traits\DeprecateTrait;
    use Traits\FactoryTrait;
    use Traits\FileTrait;
    use Traits\FromCallableTrait;
    use Traits\LazyTrait;
    use Traits\ParentTrait;
    use Traits\PropertyTrait;
    use Traits\PublicTrait;
    use Traits\ShareTrait;
    use Traits\SyntheticTrait;
    use Traits\TagTrait;

    public const FACTORY = 'services';

    private bool $destructed = false;

    public function __construct(
        private ContainerBuilder $container,
        private array $instanceof,
        private bool $allowParent,
        ServicesConfigurator $parent,
        Definition $definition,
        ?string $id,
        array $defaultTags,
        private ?string $path = null,
    ) {
        parent::__construct($parent, $definition, $id, $defaultTags);
    }

    public function __destruct()
    {
        if ($this->destructed) {
            return;
        }
        $this->destructed = true;

        parent::__destruct();

        $this->container->removeBindings($this->id);
        $this->container->setDefinition($this->id, $this->definition->setInstanceofConditionals($this->instanceof));
    }
}
