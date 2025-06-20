<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Fixtures\Doctrine\SingleTableInheritance;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "student" = "JMS\Serializer\Tests\Fixtures\Doctrine\SingleTableInheritance\Student",
 *     "teacher" = "JMS\Serializer\Tests\Fixtures\Doctrine\SingleTableInheritance\Teacher",
 * })
 */
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: Types::STRING)]
#[ORM\DiscriminatorMap(['student' => Student::class, 'teacher' => Teacher::class])]
abstract class Person extends AbstractModel
{
    /**
     * @ORM\Id
     * @ORM\Column(type = "integer")
     * @ORM\GeneratedValue(strategy = "AUTO")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;
}
