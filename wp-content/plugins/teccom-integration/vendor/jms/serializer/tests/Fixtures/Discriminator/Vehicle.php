<?php

declare(strict_types=1);

namespace JMS\Serializer\Tests\Fixtures\Discriminator;

use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\Discriminator(field = "type", map = {
 *    "car": "JMS\Serializer\Tests\Fixtures\Discriminator\Car",
 *    "moped": "JMS\Serializer\Tests\Fixtures\Discriminator\Moped",
 * })
 */
#[Serializer\Discriminator(field: 'type', map: ['car' => 'JMS\Serializer\Tests\Fixtures\Discriminator\Car', 'moped' => 'JMS\Serializer\Tests\Fixtures\Discriminator\Moped'])]
abstract class Vehicle
{
    /** @Serializer\Type("integer") */
    #[Serializer\Type(name: 'integer')]
    public $km;

    public function __construct($km)
    {
        $this->km = (int) $km;
    }
}
