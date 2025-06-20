<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Tests\Tester;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Tester\ApplicationTester;

class ApplicationTesterTest extends TestCase
{
    protected Application $application;
    protected ApplicationTester $tester;

    protected function setUp(): void
    {
        $this->application = new Application();
        $this->application->setAutoExit(false);
        $this->application->register('foo')
            ->addArgument('foo')
            ->setCode(function (OutputInterface $output): int {
                $output->writeln('foo');

                return 0;
            })
        ;

        $this->tester = new ApplicationTester($this->application);
        $this->tester->run(['command' => 'foo', 'foo' => 'bar'], ['interactive' => false, 'decorated' => false, 'verbosity' => Output::VERBOSITY_VERBOSE]);
    }

    public function testRun()
    {
        $this->assertFalse($this->tester->getInput()->isInteractive(), '->execute() takes an interactive option');
        $this->assertFalse($this->tester->getOutput()->isDecorated(), '->execute() takes a decorated option');
        $this->assertEquals(Output::VERBOSITY_VERBOSE, $this->tester->getOutput()->getVerbosity(), '->execute() takes a verbosity option');
    }

    public function testGetInput()
    {
        $this->assertEquals('bar', $this->tester->getInput()->getArgument('foo'), '->getInput() returns the current input instance');
    }

    public function testGetOutput()
    {
        rewind($this->tester->getOutput()->getStream());
        $this->assertEquals('foo'.\PHP_EOL, stream_get_contents($this->tester->getOutput()->getStream()), '->getOutput() returns the current output instance');
    }

    public function testGetDisplay()
    {
        $this->assertEquals('foo'.\PHP_EOL, $this->tester->getDisplay(), '->getDisplay() returns the display of the last execution');
    }

    public function testSetInputs()
    {
        $application = new Application();
        $application->setAutoExit(false);
        $application->register('foo')->setCode(function (InputInterface $input, OutputInterface $output): int {
            $helper = new QuestionHelper();
            $helper->ask($input, $output, new Question('Q1'));
            $helper->ask($input, $output, new Question('Q2'));
            $helper->ask($input, $output, new Question('Q3'));

            return 0;
        });
        $tester = new ApplicationTester($application);

        $tester->setInputs(['I1', 'I2', 'I3']);
        $tester->run(['command' => 'foo']);

        $tester->assertCommandIsSuccessful();
        $this->assertEquals('Q1Q2Q3', $tester->getDisplay(true));
    }

    public function testGetStatusCode()
    {
        $this->tester->assertCommandIsSuccessful('->getStatusCode() returns the status code');
    }

    public function testErrorOutput()
    {
        $application = new Application();
        $application->setAutoExit(false);
        $application->register('foo')
            ->addArgument('foo')
            ->setCode(function (OutputInterface $output): int {
                $output->getErrorOutput()->write('foo');

                return 0;
            })
        ;

        $tester = new ApplicationTester($application);
        $tester->run(
            ['command' => 'foo', 'foo' => 'bar'],
            ['capture_stderr_separately' => true]
        );

        $this->assertSame('foo', $tester->getErrorOutput());
    }
}
