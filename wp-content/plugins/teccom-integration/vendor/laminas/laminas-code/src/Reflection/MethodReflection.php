<?php

namespace Laminas\Code\Reflection;

use ReflectionMethod as PhpReflectionMethod;
use ReflectionParameter as PhpReflectionParameter;
use ReturnTypeWillChange;

use function array_key_exists;
use function array_map;
use function array_shift;
use function array_slice;
use function class_exists;
use function count;
use function file;
use function file_exists;
use function implode;
use function is_array;
use function rtrim;
use function strlen;
use function substr;
use function token_get_all;
use function token_name;
use function var_export;

use const FILE_IGNORE_NEW_LINES;

class MethodReflection extends PhpReflectionMethod implements ReflectionInterface
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
     * Retrieve method DocBlock reflection
     *
     * @return DocBlockReflection|false
     */
    public function getDocBlock()
    {
        if ('' == $this->getDocComment()) {
            return false;
        }

        return new DocBlockReflection($this);
    }

    /**
     * Get start line (position) of method
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
     * Get reflection of declaring class
     *
     * @return ClassReflection
     */
    #[ReturnTypeWillChange]
    public function getDeclaringClass()
    {
        $phpReflection     = parent::getDeclaringClass();
        $laminasReflection = new ClassReflection($phpReflection->getName());
        unset($phpReflection);

        return $laminasReflection;
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
    #[ReturnTypeWillChange]
    public function getPrototype($format = self::PROTOTYPE_AS_ARRAY)
    {
        $returnType = 'mixed';
        $docBlock   = $this->getDocBlock();
        if ($docBlock) {
            $return      = $docBlock->getTag('return');
            $returnTypes = $return->getTypes();
            $returnType  = count($returnTypes) > 1 ? implode('|', $returnTypes) : $returnTypes[0];
        }

        $declaringClass = $this->getDeclaringClass();
        $prototype      = [
            'namespace'  => $declaringClass->getNamespaceName(),
            'class'      => substr($declaringClass->getName(), strlen($declaringClass->getNamespaceName()) + 1),
            'name'       => $this->getName(),
            'visibility' => $this->isPublic() ? 'public' : ($this->isPrivate() ? 'private' : 'protected'),
            'return'     => $returnType,
            'arguments'  => [],
        ];

        $parameters = $this->getParameters();
        foreach ($parameters as $parameter) {
            $prototype['arguments'][$parameter->getName()] = [
                'type'     => $parameter->detectType(),
                'required' => ! $parameter->isOptional(),
                'by_ref'   => $parameter->isPassedByReference(),
                'default'  => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
            ];

            if ($parameter->isPromoted()) {
                $prototype['arguments'][$parameter->getName()]['promoted'] = true;
                if ($parameter->isPublicPromoted()) {
                    $prototype['arguments'][$parameter->getName()]['visibility'] = 'public';
                } elseif ($parameter->isProtectedPromoted()) {
                    $prototype['arguments'][$parameter->getName()]['visibility'] = 'protected';
                } elseif ($parameter->isPrivatePromoted()) {
                    $prototype['arguments'][$parameter->getName()]['visibility'] = 'private';
                }
            }
        }

        if ($format == self::PROTOTYPE_AS_STRING) {
            $line = $prototype['visibility'] . ' ' . $prototype['return'] . ' ' . $prototype['name'] . '(';
            $args = [];
            foreach ($prototype['arguments'] as $name => $argument) {
                $argsLine =
                    (
                        array_key_exists('visibility', $argument)
                            ? $argument['visibility'] . ' '
                            : ''
                    ) . (
                        $argument['type']
                            ? $argument['type'] . ' '
                            : ''
                    ) . (
                        $argument['by_ref']
                            ? '&'
                            : ''
                    ) . '$' . $name;
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
     * Get all method parameter reflection objects
     *
     * @return list<ParameterReflection>
     */
    #[ReturnTypeWillChange]
    public function getParameters()
    {
        $method = [$this->getDeclaringClass()->getName(), $this->getName()];

        return array_map(
            static fn (PhpReflectionParameter $parameter): ParameterReflection
                => new ParameterReflection($method, $parameter->getName()),
            parent::getParameters()
        );
    }

    /**
     * Get method contents
     *
     * @param  bool $includeDocBlock
     * @return string
     */
    public function getContents($includeDocBlock = true)
    {
        $docComment = $this->getDocComment();
        $content    = $includeDocBlock && ! empty($docComment) ? $docComment . "\n" : '';
        $content   .= $this->extractMethodContents();

        return $content;
    }

    /**
     * Get method body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->extractMethodContents(true);
    }

    /**
     * Tokenize method string and return concatenated body
     *
     * @param bool $bodyOnly
     * @return string
     */
    protected function extractMethodContents($bodyOnly = false)
    {
        $fileName = $this->getFileName();

        if ((class_exists($this->class) && false === $fileName) || ! file_exists($fileName)) {
            return '';
        }

        $lines = array_slice(
            file($fileName, FILE_IGNORE_NEW_LINES),
            $this->getStartLine() - 1,
            $this->getEndLine() - ($this->getStartLine() - 1),
            true
        );

        $functionLine = implode("\n", $lines);
        $tokens       = token_get_all('<?php ' . $functionLine);

        //remove first entry which is php open tag
        array_shift($tokens);

        if (! count($tokens)) {
            return '';
        }

        $capture    = false;
        $firstBrace = false;
        $body       = '';

        foreach ($tokens as $key => $token) {
            $tokenType  = is_array($token) ? token_name($token[0]) : $token;
            $tokenValue = is_array($token) ? $token[1] : $token;

            switch ($tokenType) {
                case 'T_FINAL':
                case 'T_ABSTRACT':
                case 'T_PUBLIC':
                case 'T_PROTECTED':
                case 'T_PRIVATE':
                case 'T_STATIC':
                case 'T_FUNCTION':
                    // check to see if we have a valid function
                    // then check if we are inside function and have a closure
                    if ($this->isValidFunction($tokens, $key, $this->getName())) {
                        if ($bodyOnly === false) {
                            //if first instance of tokenType grab prefixed whitespace
                            //and append to body
                            if ($capture === false) {
                                $body .= $this->extractPrefixedWhitespace($tokens, $key);
                            }
                            $body .= $tokenValue;
                        }

                        $capture = true;
                    } else {
                        //closure test
                        if ($firstBrace && $tokenType == 'T_FUNCTION') {
                            $body .= $tokenValue;
                            break;
                        }
                        $capture = false;
                        break;
                    }
                    break;

                case '{':
                    if ($capture === false) {
                        break;
                    }

                    if ($firstBrace === false) {
                        $firstBrace = true;
                        if ($bodyOnly === true) {
                            break;
                        }
                    }

                    $body .= $tokenValue;
                    break;

                case '}':
                    if ($capture === false) {
                        break;
                    }

                    //check to see if this is the last brace
                    if ($this->isEndingBrace($tokens, $key)) {
                        //capture the end brace if not bodyOnly
                        if ($bodyOnly === false) {
                            $body .= $tokenValue;
                        }

                        break 2;
                    }

                    $body .= $tokenValue;
                    break;

                default:
                    if ($capture === false) {
                        break;
                    }

                    // if returning body only wait for first brace before capturing
                    if ($bodyOnly === true && $firstBrace !== true) {
                        break;
                    }

                    $body .= $tokenValue;
                    break;
            }
        }

        //remove ending whitespace and return
        return rtrim($body);
    }

    /**
     * Take current position and find any whitespace
     *
     * @param array $haystack
     * @param int $position
     * @return string
     */
    protected function extractPrefixedWhitespace($haystack, $position)
    {
        $content = '';
        $count   = count($haystack);
        if ($position + 1 == $count) {
            return $content;
        }

        for ($i = $position - 1; $i >= 0; $i--) {
            $tokenType  = is_array($haystack[$i]) ? token_name($haystack[$i][0]) : $haystack[$i];
            $tokenValue = is_array($haystack[$i]) ? $haystack[$i][1] : $haystack[$i];

            //search only for whitespace
            if ($tokenType == 'T_WHITESPACE') {
                $content .= $tokenValue;
            } else {
                break;
            }
        }

        return $content;
    }

    /**
     * Test for ending brace
     *
     * @param array $haystack
     * @param int $position
     * @return bool|null
     */
    protected function isEndingBrace($haystack, $position)
    {
        $count = count($haystack);

        //advance one position
        $position += 1;

        if ($position == $count) {
            return true;
        }

        for ($i = $position; $i < $count; $i++) {
            $tokenType = is_array($haystack[$i]) ? token_name($haystack[$i][0]) : $haystack[$i];
            switch ($tokenType) {
                case 'T_FINAL':
                case 'T_ABSTRACT':
                case 'T_PUBLIC':
                case 'T_PROTECTED':
                case 'T_PRIVATE':
                case 'T_STATIC':
                    return true;

                case 'T_FUNCTION':
                    // If a function is encountered and that function is not a closure
                    // then return true.  otherwise the function is a closure, return false
                    if ($this->isValidFunction($haystack, $i)) {
                        return true;
                    }
                    return false;

                case '}':
                case ';':
                case 'T_BREAK':
                case 'T_CATCH':
                case 'T_DO':
                case 'T_ECHO':
                case 'T_ELSE':
                case 'T_ELSEIF':
                case 'T_EVAL':
                case 'T_EXIT':
                case 'T_FINALLY':
                case 'T_FOR':
                case 'T_FOREACH':
                case 'T_GOTO':
                case 'T_IF':
                case 'T_INCLUDE':
                case 'T_INCLUDE_ONCE':
                case 'T_PRINT':
                case 'T_STRING':
                case 'T_STRING_VARNAME':
                case 'T_THROW':
                case 'T_USE':
                case 'T_VARIABLE':
                case 'T_WHILE':
                case 'T_YIELD':
                    return false;
            }
        }

        return null;
    }

    /**
     * Test to see if current position is valid function or
     * closure.  Returns true if it's a function and NOT a closure
     *
     * @param array $haystack
     * @param int $position
     * @param string $functionName
     * @return bool
     */
    protected function isValidFunction($haystack, $position, $functionName = null)
    {
        $isValid = false;
        $count   = count($haystack);
        for ($i = $position + 1; $i < $count; $i++) {
            $tokenType  = is_array($haystack[$i]) ? token_name($haystack[$i][0]) : $haystack[$i];
            $tokenValue = is_array($haystack[$i]) ? $haystack[$i][1] : $haystack[$i];

            //check for occurrence of ( or
            if ($tokenType == 'T_STRING') {
                //check to see if function name is passed, if so validate against that
                if ($functionName !== null && $tokenValue != $functionName) {
                    $isValid = false;
                    break;
                }

                $isValid = true;
                break;
            } elseif ($tokenValue == '(') {
                break;
            }
        }

        return $isValid;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return parent::__toString();
    }

    public function __toString(): string
    {
        return parent::__toString();
    }
}
