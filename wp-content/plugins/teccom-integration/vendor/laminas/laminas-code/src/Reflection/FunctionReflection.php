<?php

namespace Laminas\Code\Reflection;

use ReflectionFunction;
use ReflectionParameter;
use ReturnTypeWillChange;

use function array_map;
use function array_slice;
use function count;
use function file;
use function implode;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function sprintf;
use function strlen;
use function strrpos;
use function substr;
use function var_export;

use const FILE_IGNORE_NEW_LINES;

class FunctionReflection extends ReflectionFunction implements ReflectionInterface
{
    /**
     * Constant use in @MethodReflection to display prototype as an array
     */
    public const PROTOTYPE_AS_ARRAY = 'prototype_as_array';

    /**
     * Constant use in @MethodReflection to display prototype as a string
     */
    public const PROTOTYPE_AS_STRING = 'prototype_as_string';

    /**
     * Get function DocBlock
     *
     * @throws Exception\InvalidArgumentException
     * @return DocBlockReflection
     */
    public function getDocBlock()
    {
        if ('' == ($comment = $this->getDocComment())) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s does not have a DocBlock',
                $this->getName()
            ));
        }

        return new DocBlockReflection($comment);
    }

    /**
     * Get start line (position) of function
     *
     * @param  bool $includeDocComment
     * @return int
     */
    #[ReturnTypeWillChange]
    public function getStartLine($includeDocComment = false)
    {
        if ($includeDocComment) {
            if ($this->getDocComment() != '') {
                return $this->getDocBlock()->getStartLine();
            }
        }

        return parent::getStartLine();
    }

    /**
     * Get contents of function
     *
     * @param  bool   $includeDocBlock
     * @return string
     */
    public function getContents($includeDocBlock = true)
    {
        $fileName = $this->getFileName();
        if (false === $fileName) {
            return '';
        }

        $startLine = $this->getStartLine();
        $endLine   = $this->getEndLine();

        // eval'd protect
        if (preg_match('#\((\d+)\) : eval\(\)\'d code$#', $fileName, $matches)) {
            $fileName  = preg_replace('#\(\d+\) : eval\(\)\'d code$#', '', $fileName);
            $startLine = $endLine = $matches[1];
        }

        $lines = array_slice(
            file($fileName, FILE_IGNORE_NEW_LINES),
            $startLine - 1,
            $endLine - ($startLine - 1),
            true
        );

        $functionLine = implode("\n", $lines);

        $content = '';
        if ($this->isClosure()) {
            preg_match('#function\s*\([^\)]*\)\s*(use\s*\([^\)]+\))?\s*\{(.*\;)?\s*\}#s', $functionLine, $matches);
            if (isset($matches[0])) {
                $content = $matches[0];
            }
        } else {
            $name = substr($this->getName(), strrpos($this->getName(), '\\') + 1);
            preg_match(
                '#function\s+' . preg_quote($name) . '\s*\([^\)]*\)\s*{([^{}]+({[^}]+})*[^}]+)?}#',
                $functionLine,
                $matches
            );
            if (isset($matches[0])) {
                $content = $matches[0];
            }
        }

        $docComment = $this->getDocComment();

        return $includeDocBlock && $docComment ? $docComment . "\n" . $content : $content;
    }

    /**
     * Get method prototype
     *
     * @deprecated this method is unreliable, and should not be used: it will be removed in the next major release.
     *             It may crash on parameters with union types, and will return relative types, instead of
     *             FQN references
     *
     * @param string $format
     * @return array|string
     */
    public function getPrototype($format = self::PROTOTYPE_AS_ARRAY)
    {
        $docBlock    = $this->getDocBlock();
        $return      = $docBlock->getTag('return');
        $returnTypes = $return->getTypes();
        $returnType  = count($returnTypes) > 1 ? implode('|', $returnTypes) : $returnTypes[0];

        $prototype = [
            'namespace' => $this->getNamespaceName(),
            'name'      => substr($this->getName(), strlen($this->getNamespaceName()) + 1),
            'return'    => $returnType,
            'arguments' => [],
        ];

        $parameters = $this->getParameters();
        foreach ($parameters as $parameter) {
            $prototype['arguments'][$parameter->getName()] = [
                'type'     => $parameter->detectType(),
                'required' => ! $parameter->isOptional(),
                'by_ref'   => $parameter->isPassedByReference(),
                'default'  => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
            ];
        }

        if ($format == self::PROTOTYPE_AS_STRING) {
            $line = $prototype['return'] . ' ' . $prototype['name'] . '(';
            $args = [];
            foreach ($prototype['arguments'] as $name => $argument) {
                $argsLine = ($argument['type']
                    ? $argument['type'] . ' '
                    : '') . ($argument['by_ref'] ? '&' : '') . '$' . $name;
                if (! $argument['required']) {
                    $argsLine .= ' = ' . var_export($argument['default'], true);
                }
                $args[] = $argsLine;
            }
            $line .= implode(', ', $args);
            $line .= ')';

            return $line;
        }

        return $prototype;
    }

    /**
     * Get function parameters
     *
     * @return list<ParameterReflection>
     */
    #[ReturnTypeWillChange]
    public function getParameters()
    {
        $name = $this->getName();

        return array_map(
            static fn (ReflectionParameter $parameter): ParameterReflection
                => new ParameterReflection($name, $parameter->getName()),
            parent::getParameters()
        );
    }

    /**
     * Get return type tag
     *
     * @deprecated this method is unreliable, and will be dropped in the next major release.
     *             If you are attempting to inspect the return type of an expression, please
     *             use more reliable tools, such as `vimeo/psalm` or `phpstan/phpstan` instead.
     *
     * @throws Exception\InvalidArgumentException
     * @return DocBlockReflection
     */
    public function getReturn()
    {
        $docBlock = $this->getDocBlock();
        if (! $docBlock->hasTag('return')) {
            throw new Exception\InvalidArgumentException(
                'Function does not specify an @return annotation tag; cannot determine return type'
            );
        }

        $tag = $docBlock->getTag('return');

        return new DocBlockReflection('@return ' . $tag->getDescription());
    }

    /**
     * Get method body
     *
     * @return string|false
     */
    public function getBody()
    {
        $fileName = $this->getFileName();
        if (false === $fileName) {
            throw new Exception\InvalidArgumentException(
                'Cannot determine internals functions body'
            );
        }

        $startLine = $this->getStartLine();
        $endLine   = $this->getEndLine();

        // eval'd protect
        if (preg_match('#\((\d+)\) : eval\(\)\'d code$#', $fileName, $matches)) {
            $fileName  = preg_replace('#\(\d+\) : eval\(\)\'d code$#', '', $fileName);
            $startLine = $endLine = $matches[1];
        }

        $lines = array_slice(
            file($fileName, FILE_IGNORE_NEW_LINES),
            $startLine - 1,
            $endLine - ($startLine - 1),
            true
        );

        $functionLine = implode("\n", $lines);

        $body = false;
        if ($this->isClosure()) {
            preg_match('#function\s*\([^\)]*\)\s*(use\s*\([^\)]+\))?\s*\{(.*\;)\s*\}#s', $functionLine, $matches);
            if (isset($matches[2])) {
                $body = $matches[2];
            }
        } else {
            $name = substr($this->getName(), strrpos($this->getName(), '\\') + 1);
            preg_match('#function\s+' . $name . '\s*\([^\)]*\)\s*{([^{}]+({[^}]+})*[^}]+)}#', $functionLine, $matches);
            if (isset($matches[1])) {
                $body = $matches[1];
            }
        }

        return $body;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->__toString();
    }

    /**
     * Required due to bug in php
     *
     * @psalm-external-mutation-free
     */
    public function __toString(): string
    {
        return parent::__toString();
    }
}
