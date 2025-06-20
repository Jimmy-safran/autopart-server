<?php

namespace Laminas\Code\Generator;

use function is_array;
use function is_string;
use function sprintf;

abstract class AbstractMemberGenerator extends AbstractGenerator
{
    public const FLAG_ABSTRACT        = 0x01;
    public const FLAG_FINAL           = 0x02;
    public const FLAG_STATIC          = 0x04;
    public const FLAG_INTERFACE       = 0x08;
    public const FLAG_PUBLIC          = 0x10;
    public const FLAG_PROTECTED       = 0x20;
    public const FLAG_PRIVATE         = 0x40;
    public const VISIBILITY_PUBLIC    = 'public';
    public const VISIBILITY_PROTECTED = 'protected';
    public const VISIBILITY_PRIVATE   = 'private';

    protected ?DocBlockGenerator $docBlock = null;

    protected string $name = '';

    protected int $flags = self::FLAG_PUBLIC;

    /**
     * @param  int|int[] $flags
     * @return static
     */
    public function setFlags($flags)
    {
        if (is_array($flags)) {
            $flagsArray = $flags;
            $flags      = 0x00;
            foreach ($flagsArray as $flag) {
                $flags |= $flag;
            }
        }
        // check that visibility is one of three
        $this->flags = $flags;

        return $this;
    }

    /**
     * @param  int $flag
     * @return static
     */
    public function addFlag($flag)
    {
        $this->setFlags($this->flags | $flag);
        return $this;
    }

    /**
     * @param  int $flag
     * @return static
     */
    public function removeFlag($flag)
    {
        $this->setFlags($this->flags & ~$flag);
        return $this;
    }

    /**
     * @param  bool $isAbstract
     * @return static
     */
    public function setAbstract($isAbstract)
    {
        return $isAbstract ? $this->addFlag(self::FLAG_ABSTRACT) : $this->removeFlag(self::FLAG_ABSTRACT);
    }

    /**
     * @return bool
     */
    public function isAbstract()
    {
        return (bool) ($this->flags & self::FLAG_ABSTRACT);
    }

    /**
     * @param  bool $isInterface
     * @return static
     */
    public function setInterface($isInterface)
    {
        return $isInterface ? $this->addFlag(self::FLAG_INTERFACE) : $this->removeFlag(self::FLAG_INTERFACE);
    }

    /**
     * @return bool
     */
    public function isInterface()
    {
        return (bool) ($this->flags & self::FLAG_INTERFACE);
    }

    /**
     * @param  bool $isFinal
     * @return static
     */
    public function setFinal($isFinal)
    {
        return $isFinal ? $this->addFlag(self::FLAG_FINAL) : $this->removeFlag(self::FLAG_FINAL);
    }

    /**
     * @return bool
     */
    public function isFinal()
    {
        return (bool) ($this->flags & self::FLAG_FINAL);
    }

    /**
     * @param  bool $isStatic
     * @return static
     */
    public function setStatic($isStatic)
    {
        return $isStatic ? $this->addFlag(self::FLAG_STATIC) : $this->removeFlag(self::FLAG_STATIC);
    }

    /**
     * @return bool
     */
    public function isStatic()
    {
        return (bool) ($this->flags & self::FLAG_STATIC); // is FLAG_STATIC in flags
    }

    /**
     * @param  string $visibility
     * @return static
     */
    public function setVisibility($visibility)
    {
        switch ($visibility) {
            case self::VISIBILITY_PUBLIC:
                $this->removeFlag(self::FLAG_PRIVATE | self::FLAG_PROTECTED); // remove both
                $this->addFlag(self::FLAG_PUBLIC);
                break;
            case self::VISIBILITY_PROTECTED:
                $this->removeFlag(self::FLAG_PUBLIC | self::FLAG_PRIVATE); // remove both
                $this->addFlag(self::FLAG_PROTECTED);
                break;
            case self::VISIBILITY_PRIVATE:
                $this->removeFlag(self::FLAG_PUBLIC | self::FLAG_PROTECTED); // remove both
                $this->addFlag(self::FLAG_PRIVATE);
                break;
        }

        return $this;
    }

    /**
     * @psalm-return static::VISIBILITY_*
     */
    public function getVisibility()
    {
        switch (true) {
            case $this->flags & self::FLAG_PROTECTED:
                return self::VISIBILITY_PROTECTED;
            case $this->flags & self::FLAG_PRIVATE:
                return self::VISIBILITY_PRIVATE;
            default:
                return self::VISIBILITY_PUBLIC;
        }
    }

    /**
     * @param  string $name
     * @return static
     */
    public function setName($name)
    {
        $this->name = (string) $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param  DocBlockGenerator|string $docBlock
     * @throws Exception\InvalidArgumentException
     * @return static
     */
    public function setDocBlock($docBlock)
    {
        if (is_string($docBlock)) {
            $docBlock = new DocBlockGenerator($docBlock);
        } elseif (! $docBlock instanceof DocBlockGenerator) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s is expecting either a string, array or an instance of %s\DocBlockGenerator',
                __METHOD__,
                __NAMESPACE__
            ));
        }

        $this->docBlock = $docBlock;

        return $this;
    }

    public function removeDocBlock(): void
    {
        $this->docBlock = null;
    }

    /**
     * @return DocBlockGenerator|null
     */
    public function getDocBlock()
    {
        return $this->docBlock;
    }
}
