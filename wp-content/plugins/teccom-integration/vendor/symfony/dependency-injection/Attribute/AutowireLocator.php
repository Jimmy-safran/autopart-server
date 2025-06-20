<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Attribute;

use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\TypedReference;
use Symfony\Contracts\Service\Attribute\SubscribedService;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Autowires a service locator based on a tag name or an explicit list of key => service-type pairs.
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class AutowireLocator extends Autowire
{
    /**
     * @see ServiceSubscriberInterface::getSubscribedServices()
     *
     * @param string|array<string|Autowire|SubscribedService> $services              A tag name or an explicit list of service ids
     * @param string|null                                     $indexAttribute        The name of the attribute that defines the key referencing each service in the locator
     * @param string|null                                     $defaultIndexMethod    The static method that should be called to get each service's key when their tag doesn't define the previous attribute
     * @param string|null                                     $defaultPriorityMethod The static method that should be called to get each service's priority when their tag doesn't define the "priority" attribute
     * @param string|array                                    $exclude               A service id or a list of service ids to exclude
     * @param bool                                            $excludeSelf           Whether to automatically exclude the referencing service from the locator
     */
    public function __construct(
        string|array $services,
        ?string $indexAttribute = null,
        ?string $defaultIndexMethod = null,
        ?string $defaultPriorityMethod = null,
        string|array $exclude = [],
        bool $excludeSelf = true,
    ) {
        if (\is_string($services)) {
            parent::__construct(new ServiceLocatorArgument(new TaggedIteratorArgument($services, $indexAttribute, $defaultIndexMethod, true, $defaultPriorityMethod, (array) $exclude, $excludeSelf)));

            return;
        }

        $references = [];

        foreach ($services as $key => $type) {
            $attributes = [];

            if ($type instanceof Autowire) {
                $references[$key] = $type;
                continue;
            }

            if ($type instanceof SubscribedService) {
                $key = $type->key ?? $key;
                $attributes = $type->attributes;
                $type = ($type->nullable ? '?' : '').($type->type ?? throw new InvalidArgumentException(\sprintf('When "%s" is used, a type must be set.', SubscribedService::class)));
            }

            if (!\is_string($type) || !preg_match('/(?(DEFINE)(?<cn>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+))(?(DEFINE)(?<fqcn>(?&cn)(?:\\\\(?&cn))*+))^\??(?&fqcn)(?:(?:\|(?&fqcn))*+|(?:&(?&fqcn))*+)$/', $type)) {
                throw new InvalidArgumentException(\sprintf('"%s" is not a PHP type for key "%s".', \is_string($type) ? $type : get_debug_type($type), $key));
            }
            $optionalBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;
            if ('?' === $type[0]) {
                $type = substr($type, 1);
                $optionalBehavior = ContainerInterface::IGNORE_ON_INVALID_REFERENCE;
            }
            if (\is_int($name = $key)) {
                $key = $type;
                $name = null;
            }

            $references[$key] = new TypedReference($type, $type, $optionalBehavior, $name, $attributes);
        }

        parent::__construct(new ServiceLocatorArgument($references));
    }
}
