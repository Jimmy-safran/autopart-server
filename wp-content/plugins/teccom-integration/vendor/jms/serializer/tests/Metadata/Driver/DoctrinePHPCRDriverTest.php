<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Metadata\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\PHPCR\Configuration;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver as DoctrinePHPCRDriver;
use Doctrine\ODM\PHPCR\Mapping\Driver\AttributeDriver as AttributeDoctrinePHPCRDriver;
use Doctrine\Persistence\ManagerRegistry;
use JMS\Serializer\Metadata\Driver\AnnotationDriver;
use JMS\Serializer\Metadata\Driver\AnnotationOrAttributeDriver;
use JMS\Serializer\Metadata\Driver\DoctrinePHPCRTypeDriver;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Tests\Fixtures\BlogPost;
use JMS\Serializer\Tests\Fixtures\DoctrinePHPCR\Author;
use JMS\Serializer\Tests\Fixtures\DoctrinePHPCR\BlogPost as BlogPostPHPCR;
use JMS\Serializer\Tests\Fixtures\DoctrinePHPCR\Comment;
use PHPCR\SessionInterface;
use PHPUnit\Framework\TestCase;

class DoctrinePHPCRDriverTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(Configuration::class)) {
            $this->markTestSkipped('PHPCR not available');
        }
    }

    public function getMetadata()
    {
        $refClass = new \ReflectionClass(BlogPostPHPCR::class);

        return $this->getDoctrinePHPCRDriver()->loadMetadataForClass($refClass);
    }

    public function testTypelessPropertyIsGivenTypeFromDoctrineMetadata()
    {
        $metadata = $this->getMetadata();
        self::assertEquals(
            ['name' => 'DateTime', 'params' => []],
            $metadata->propertyMetadata['createdAt']->type,
        );
    }

    public function testSingleValuedAssociationIsProperlyHinted()
    {
        $metadata = $this->getMetadata();
        self::assertEquals(
            ['name' => Author::class, 'params' => []],
            $metadata->propertyMetadata['author']->type,
        );
    }

    public function testMultiValuedAssociationIsProperlyHinted()
    {
        $metadata = $this->getMetadata();

        self::assertEquals(
            [
                'name' => 'ArrayCollection',
                'params' => [
                    ['name' => Comment::class, 'params' => []],
                ],
            ],
            $metadata->propertyMetadata['comments']->type,
        );
    }

    public function testTypeGuessByDoctrineIsOverwrittenByDelegateDriver()
    {
        $metadata = $this->getMetadata();

        // This would be guessed as boolean but we've overridden it to integer
        self::assertEquals(
            ['name' => 'integer', 'params' => []],
            $metadata->propertyMetadata['published']->type,
        );
    }

    public function testNonDoctrineDocumentClassIsNotModified()
    {
        // Note: Using regular BlogPost fixture here instead of Doctrine fixture
        // because it has no Doctrine metadata.
        $refClass = new \ReflectionClass(BlogPost::class);

        $plainMetadata = $this->getAnnotationDriver()->loadMetadataForClass($refClass);
        $doctrineMetadata = $this->getDoctrinePHPCRDriver()->loadMetadataForClass($refClass);

        // Do not compare timestamps
        if (abs($doctrineMetadata->createdAt - $plainMetadata->createdAt) < 2) {
            $plainMetadata->createdAt = $doctrineMetadata->createdAt;
        }

        self::assertEquals($plainMetadata, $doctrineMetadata);
    }

    protected function getDocumentManager()
    {
        $config = new Configuration();
        $config->setProxyDir(sys_get_temp_dir() . '/JMSDoctrineTestProxies');
        $config->setProxyNamespace('JMS\Tests\Proxies');
        if (class_exists(DoctrinePHPCRDriver::class)) {
            $config->setMetadataDriverImpl(
                new DoctrinePHPCRDriver(new AnnotationReader(), __DIR__ . '/../../Fixtures/DoctrinePHPCR'),
            );
        } else {
            $config->setMetadataDriverImpl(
                new AttributeDoctrinePHPCRDriver([__DIR__ . '/../../Fixtures/DoctrinePHPCR']),
            );
        }

        $session = $this->getMockBuilder(SessionInterface::class)->getMock();

        return DocumentManager::create($session, $config);
    }

    public function getAnnotationDriver()
    {
        if (class_exists(DoctrinePHPCRDriver::class)) {
            return new AnnotationDriver(new AnnotationReader(), new IdenticalPropertyNamingStrategy());
        }

        return new AnnotationOrAttributeDriver(new IdenticalPropertyNamingStrategy());
    }

    protected function getDoctrinePHPCRDriver()
    {
        $registry = $this->getMockBuilder(ManagerRegistry::class)->getMock();
        $registry->expects($this->atLeastOnce())
            ->method('getManagerForClass')
            ->willReturn($this->getDocumentManager());

        return new DoctrinePHPCRTypeDriver(
            $this->getAnnotationDriver(),
            $registry,
        );
    }
}
