# Laminas\\Code\\Generator Reference

## Abstract Classes and Interfaces

### Laminas\\Code\\Generator\\GeneratorInterface

The base interface from which all CodeGenerator classes implement provides the minimal functionality
necessary. It's *API* is as follows:

```php
interface Laminas\Code\Generator\GeneratorInterface
{
    public function generate();
}
```

### Laminas\\Code\\Generator\\AbstractGenerator

`Laminas\Code\Generator\AbstractGenerator` implements `Laminas\Code\Generator\GeneratorInterface`, and
adds some properties for tracking whether content has changed as well as the amount of indentation
that should appear before generated content. Its *API* is as follows:

```php
abstract class Laminas\Code\Generator\AbstractGenerator
    implements Laminas\Code\Generator\GeneratorInterface
{
    public function __construct(Array|Traversable $options = [])
    public function setOptions(Array $options)
    public function setSourceContent($sourceContent)
    public function getSourceContent()
    public function setSourceDirty($isSourceDirty = true)
    public function isSourceDirty()
    public function setIndentation($indentation)
    public function getIndentation()
}
```

The constructor passes the `$options` parameter to `setOptions()`.

Like most classes in Laminas, `setOptions()` compares an option key to existing setters in
the class, and passes the value on to that method if found.

`setSourceContent()` and `getSourceContent()` are intended to either set the default content for the
code being generated, or to replace said content once all generation tasks are complete.

### Laminas\\Code\\Generator\\AbstractMemberGenerator

`Laminas\Code\Generator\AbstractMemberGenerator` is a base class for generating class members --
properties and methods -- and provides accessors and mutators for establishing visibility; whether
or not the member is abstract, static, or final; and the name of the member. Its *API* is as
follows:

```php
abstract class Laminas\Code\Generator\AbstractMemberGenerator
    extends Laminas\Code\Generator\AbstractGenerator
{
    public function setAbstract($isAbstract)
    public function isAbstract()
    public function setStatic($isStatic)
    public function isStatic()
    public function setVisibility($visibility)
    public function getVisibility()
    public function setName($name)
    public function getName()
}
```

## Concrete CodeGenerator Classes

### Laminas\\Code\\Generator\\BodyGenerator

`Laminas\Code\Generator\BodyGenerator` is intended for generating arbitrary procedural code to include
within a file. As such, you simply set content for the object, and it will return that content when
you invoke `generate()`.

The *API* of the class is as follows:

```php
class Laminas\Code\Generator\BodyGenerator extends Laminas\Code\Generator\AbstractGenerator
{
    public function setContent($content)
    public function getContent()
    public function generate()
}
```

### Laminas\\Code\\Generator\\ClassGenerator

`Laminas\Code\Generator\ClassGenerator` is intended for generating *PHP* classes. The basic
functionality just generates the *PHP* class itself, as well as optionally the related *PHP*
DocBlock. Classes may implement or inherit from other classes, and may be marked as abstract.
Utilizing other code generator classes, you can also attach class constants, properties, and
methods.

The *API* is as follows:

```php
class Laminas\Code\Generator\ClassGenerator extends Laminas\Code\Generator\AbstractGenerator
{
    public static function fromReflection(
        Laminas\Code\Reflection\ClassReflection $reflectionClass
    )
    public function addConstants(Array $properties)
    public function addConstant($property)
    public function getConstants()
    public function getConstant($propertyName)
    public function removeConstant($constantName)
    public function setDocblock(Laminas\Code\Generator\DocBlockGenerator $docblock)
    public function getDocblock()
    public function setName($name)
    public function getName()
    public function setAbstract($isAbstract)
    public function isAbstract()
    public function setExtendedClass($extendedClass)
    public function getExtendedClass()
    public function hasExtentedClass()
    public function removeExtentedClass()
    public function setImplementedInterfaces(Array $implementedInterfaces)
    public function getImplementedInterfaces()
    public function addProperties(Array $properties)
    public function addProperty($property)
    public function getProperties()
    public function getProperty($propertyName)
    public function removeProperty($propertyName)
    public function addMethods(Array $methods)
    public function addMethod(
        $name,
        Array $parameters = [],
        $flags = Laminas\Code\Generator\MethodGenerator::FLAG_PUBLIC,
        $body = null,
        $docBlock = null
    )
    public function getMethods()
    public function getMethod($methodName)
    public function hasMethod($methodName)
    public function hasUse($use)
    public function removeUse($use)
    public function hasUseAlias($use)
    public function removeUseAlias($use)
    public function hasImplementedInterface($implementedInterface)
    public function removeImplementedInterface($implementedInterface)
    public function isSourceDirty()
    public function generate()
}
```

The `addProperty()` method accepts an array of information that may be used to generate a
`Laminas\Code\Generator\PropertyGenerator` instance -- or simply an instance of
`Laminas\Code\Generator\PropertyGenerator`. Likewise, `addMethod()` accepts either an array of
information for generating a `Laminas\Code\Generator\MethodGenerator` instance or a concrete instance
of that class.

Note that `setDocBlock()` expects an instance of `Laminas\Code\Generator\DocBlockGenerator`.

### Laminas\\Code\\Generator\\DocBlockGenerator

`Laminas\Code\Generator\DocBlockGenerator` can be used to generate arbitrary *PHP* docblocks, including
all the standard docblock features: short and long descriptions and annotation tags.

Annotation tags may be set using the `setTag()` and `setTags()` methods; these each take either an
array describing the tag that may be passed to the `Laminas\Code\Generator\DocBlock\Tag` constructor,
or an instance of that class.

The *API* is as follows:

```php
class Laminas\Code\Generator\DocBlockGenerator extends Laminas\Code\Generator\AbstractGenerator
{
    public static function fromReflection(
        Laminas\Code\Reflection\DocblockReflection $reflectionDocblock
    )
    public function setShortDescription($shortDescription)
    public function getShortDescription()
    public function setLongDescription($longDescription)
    public function getLongDescription()
    public function setTags(Array $tags)
    public function setTag($tag)
    public function getTags()
    public function generate()
}
```

### Laminas\\Code\\Generator\\DocBlock\\Tag

`Laminas\Code\Generator\DocBlock\Tag` is intended for creating arbitrary annotation tags for inclusion
in *PHP* docblocks. Tags are expected to contain a name (the portion immediately following the '@'
symbol) and a description (everything following the tag name).

The class *API* is as follows:

```php
class Laminas\Code\Generator\DocBlock\Tag
    extends Laminas\Code\Generator\AbstractGenerator
{
    public static function fromReflection(
        Laminas\Code\Reflection\DocBlock\Tag\TagInterface $reflectionTag
    )
    public function setName($name)
    public function getName()
    public function setDescription($description)
    public function getDescription()
    public function generate()
}
```

### Laminas\\Code\\Generator\\DocBlock\\Tag\\ParamTag

`Laminas\Code\Generator\DocBlock\Tag\ParamTag` is a specialized version of
`Laminas\Code\Generator\DocBlock\Tag`, and represents a method parameter. The tag name is therefor
known ("param"), but due to the format of this annotation tag, additional information is required in
order to generate it: the parameter name and data type it represents.

The class *API* is as follows:

```php
class Laminas\Code\Generator\DocBlock\Tag\ParamTag
    extends Laminas\Code\Generator\DocBlock\Tag
{
    public static function fromReflection(
        Laminas\Code\Reflection\DocBlock\Tag\TagInterface $reflectionTagParam
    )
    public function setDatatype($datatype)
    public function getDatatype()
    public function setParamName($paramName)
    public function getParamName()
    public function generate()
}
```

### Laminas\\Code\\Generator\\DocBlock\\Tag\\ReturnTag

Like the param docblock tag variant, `Laminas\Code\Generator\DocBlock\Tag\ReturnTag` is an annotation
tag variant for representing a method return value. In this case, the annotation tag name is known
("return"), but requires a return type.

The class *API* is as follows:

```php
class Laminas\Code\Generator\DocBlock\Tag\ParamTag
    extends Laminas\Code\Generator\DocBlock\Tag
{
    public static function fromReflection(
        Laminas\Code\Reflection\DocBlock\Tag\TagInterface $reflectionTagReturn
    )
    public function setDatatype($datatype)
    public function getDatatype()
    public function generate()
}
```

### Laminas\\Code\\Generator\\FileGenerator

`Laminas\Code\Generator\FileGenerator` is used to generate the full contents of a file that will
contain *PHP* code. The file may contain classes or arbitrary *PHP* code, as well as a file-level
docblock if desired.

When adding classes to the file, you will need to pass either an array of information to pass to the
`Laminas\Code\Generator\ClassGenerator` constructor, or an instance of that class. Similarly, with
docblocks, you will need to pass information for the `Laminas\Code\Generator\DocBlockGenerator`
constructor to consume or an instance of the class.

The *API* of the class is as follows:

```php
class Laminas\Code\Generator\FileGenerator extends Laminas\Code\Generator\AbstractGenerator
{
    public function setDocblock(Laminas\Code\Generator\DocBlockGenerator $docblock)
    public function getDocblock()
    public function setRequiredFiles($requiredFiles)
    public function getRequiredFiles()
    public function setClasses(Array $classes)
    public function getClass($name = null)
    public function setClass($class)
    public function setFilename($filename)
    public function getFilename()
    public function getClasses()
    public function setBody($body)
    public function getBody()
    public function isSourceDirty()
    public function generate()
}
```

### Laminas\\Code\\Generator\\Member\\ContainerGenerator

`Laminas\Code\Generator\Member\ContainerGenerator` is used internally by
`Laminas\Code\Generator\ClassGenerator` to keep track of class members -- properties and methods alike.
These are indexed by name, using the concrete instances of the members as values.

The *API* of the class is as follows:

```php
class Laminas\Code\Generator\Member\ContainerGenerator extends ArrayObject
{
    public function __construct($type = self::TYPE_PROPERTY)
}
```

### Laminas\\Code\\Generator\\MethodGenerator

`Laminas\Code\Generator\MethodGenerator` describes a class method, and can generate both the code and
the docblock for the method. The visibility and status as static, abstract, or final may be
indicated, per its parent class, `Laminas\Code\Generator\AbstractMemberGenerator`. Finally, the
parameters and return value for the method may be specified.

Parameters may be set using `setParameter()` or `setParameters()`. In each case, a parameter should
either be an array of information to pass to the `Laminas\Code\Generator\ParameterGenerator`
constructor or an instance of that class.

The *API* of the class is as follows:

```php
class Laminas\Code\Generator\MethodGenerator
    extends Laminas\Code\Generator\AbstractMemberGenerator
{
    public static function fromReflection(
        Laminas\Code\Reflection\MethodReflection $reflectionMethod
    )
    public function setDocblock(Laminas\Code\Generator\DocBlockGenerator $docblock)
    public function getDocblock()
    public function setFinal($isFinal)
    public function setParameters(Array $parameters)
    public function setParameter($parameter)
    public function getParameters()
    public function setBody($body)
    public function getBody()
    public function generate()
}
```

### Laminas\\Code\\Generator\\ParameterGenerator

`Laminas\Code\Generator\ParameterGenerator` may be used to specify method parameters. Each parameter
may have a position (if unspecified, the order in which they are registered with the method will be
used), a default value, and a data type; a parameter name is required.

The *API* of the class is as follows:

```php
class Laminas\Code\Generator\ParameterGenerator extends Laminas\Code\Generator\AbstractGenerator
{
    public static function fromReflection(
        Laminas\Code\Reflection\ParameterReflection $reflectionParameter
    )
    public function setType($type)
    public function getType()
    public function setName($name)
    public function getName()
    public function setDefaultValue($defaultValue)
    public function getDefaultValue()
    public function setPosition($position)
    public function getPosition()
    public function getPassedByReference()
    public function setPassedByReference($passedByReference)
    public function generate()
    public function omitDefaultValue()
}
```

There are several problems that might occur when trying to set `NULL`, booleans or arrays as default
values. For this the value holder object `Laminas\Code\Generator\ParameterDefaultValueGenerator` can be
used, for example:

```php
$parameter = new Laminas\Code\Generator\ParameterGenerator();
$parameter->setDefaultValue(
    new Laminas\Code\Generator\ValueGenerator("null")
);
$parameter->setDefaultValue(
    new Laminas\Code\Generator\ValueGenerator("['foo', 'bar']")
);
```

Internally `setDefaultValue()` also converts the values which can't be expressed in *PHP* into the
value holder.

### Laminas\\Code\\Generator\\PropertyGenerator

`Laminas\Code\Generator\PropertyGenerator` describes a class property, which may be either a constant
or a variable. In each case, the property may have an optional default value associated with it.
Additionally, the visibility of variable properties may be set, per the parent class,
`Laminas\Code\Generator\AbstractMemberGenerator`.

The *API* of the class is as follows:

```php
class Laminas\Code\Generator\PropertyGenerator
    extends Laminas\Code\Generator\AbstractMemberGenerator
{
    public static function fromReflection(
        Laminas\Code\Reflection\PropertyReflection $reflectionProperty
    )
    public function setConst($const)
    public function isConst()
    public function setDefaultValue($defaultValue)
    public function getDefaultValue()
    public function generate()
    public function omitDefaultValue()
}
```
