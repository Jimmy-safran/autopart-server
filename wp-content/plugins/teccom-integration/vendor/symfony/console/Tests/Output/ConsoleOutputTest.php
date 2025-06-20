<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Tests\Output;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\Output;

class ConsoleOutputTest extends TestCase
{
    public function testConstructorWithoutFormatter()
    {
        $output = new ConsoleOutput(Output::VERBOSITY_QUIET, true);
        $this->assertEquals(Output::VERBOSITY_QUIET, $output->getVerbosity(), '__construct() takes the verbosity as its first argument');
        $this->assertNotSame($output->getFormatter(), $output->getErrorOutput()->getFormatter(), 'ErrorOutput should use it own formatter');
    }

    public function testConstructorWithFormatter()
    {
        $output = new ConsoleOutput(Output::VERBOSITY_QUIET, true, $formatter = new OutputFormatter());
        $this->assertEquals(Output::VERBOSITY_QUIET, $output->getVerbosity(), '__construct() takes the verbosity as its first argument');
        $this->assertSame($formatter, $output->getFormatter());
        $this->assertSame($formatter, $output->getErrorOutput()->getFormatter(), 'Output and ErrorOutput should use the same provided formatter');
    }

    public function testSetFormatter()
    {
        $output = new ConsoleOutput();
        $outputFormatter = new OutputFormatter();
        $output->setFormatter($outputFormatter);
        $this->assertSame($outputFormatter, $output->getFormatter());
        $this->assertSame($outputFormatter, $output->getErrorOutput()->getFormatter());
    }

    public function testSetVerbosity()
    {
        $output = new ConsoleOutput();
        $output->setVerbosity(Output::VERBOSITY_VERBOSE);
        $this->assertSame(Output::VERBOSITY_VERBOSE, $output->getVerbosity());
    }
}
