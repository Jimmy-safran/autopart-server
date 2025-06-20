<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache\Tests\Adapter;

/**
 * @group integration
 */
class RedisArrayAdapterTest extends AbstractRedisAdapterTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (!class_exists(\RedisArray::class)) {
            self::markTestSkipped('The RedisArray class is required.');
        }
        self::$redis = new \RedisArray([getenv('REDIS_HOST')], ['lazy_connect' => true]);
        self::$redis->setOption(\Redis::OPT_PREFIX, 'prefix_');
    }
}
