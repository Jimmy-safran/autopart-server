<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @group time-sensitive
 */
class ProgressBarTest extends TestCase
{
    private string|false $colSize;

    protected function setUp(): void
    {
        $this->colSize = getenv('COLUMNS');
        putenv('COLUMNS=120');
    }

    protected function tearDown(): void
    {
        putenv($this->colSize ? 'COLUMNS='.$this->colSize : 'COLUMNS');
    }

    public function testMultipleStart()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->start();
        $bar->advance();
        $bar->start();

        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---------------------------]'.
            $this->generateOutput('    1 [->--------------------------]').
            $this->generateOutput('    0 [>---------------------------]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testAdvance()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->start();
        $bar->advance();

        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---------------------------]'.
            $this->generateOutput('    1 [->--------------------------]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testResumeNoMax()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->start(null, 15);
        $bar->advance();

        rewind($output->getStream());

        $this->assertEquals(
            '   15 [--------------->------------]'.
            $this->generateOutput('   16 [---------------->-----------]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testResumeWithMax()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 5000, 0);
        $bar->start(null, 1000);

        rewind($output->getStream());

        $this->assertEquals(
            ' 1000/5000 [=====>----------------------]  20%',
            stream_get_contents($output->getStream())
        );
    }

    public function testRegularTimeEstimation()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 1_200, 0);
        $bar->start();

        $bar->advance();
        $bar->advance();

        sleep(1);

        $this->assertEquals(
            600.0,
            $bar->getEstimated()
        );
    }

    public function testRegularTimeRemainingWithDifferentStartAtAndCustomDisplay()
    {
        $this->expectNotToPerformAssertions();

        ProgressBar::setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% %remaining% %estimated%');
        $bar = new ProgressBar($this->getOutputStream(), 1_200, 0);
        $bar->setFormat('custom');
        $bar->start(1_200, 600);
    }

    public function testResumedTimeEstimation()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 1_200, 0);
        $bar->start(null, 599);
        $bar->advance();

        sleep(1);

        $this->assertEquals(
            1_200.0,
            $bar->getEstimated()
        );

        $this->assertEquals(
            600.0,
            $bar->getRemaining()
        );
    }

    public function testAdvanceWithStep()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->start();
        $bar->advance(5);

        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---------------------------]'.
            $this->generateOutput('    5 [----->----------------------]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testAdvanceMultipleTimes()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->start();
        $bar->advance(3);
        $bar->advance(2);

        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---------------------------]'.
            $this->generateOutput('    3 [--->------------------------]').
            $this->generateOutput('    5 [----->----------------------]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testAdvanceOverMax()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 10, 0);
        $bar->setProgress(9);
        $bar->advance();
        $bar->advance();

        rewind($output->getStream());
        $this->assertEquals(
            '  9/10 [=========================>--]  90%'.
            $this->generateOutput(' 10/10 [============================] 100%').
            $this->generateOutput(' 11/11 [============================] 100%'),
            stream_get_contents($output->getStream())
        );
    }

    public function testRegress()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->start();
        $bar->advance();
        $bar->advance();
        $bar->advance(-1);

        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---------------------------]'.
            $this->generateOutput('    1 [->--------------------------]').
            $this->generateOutput('    2 [-->-------------------------]').
            $this->generateOutput('    1 [->--------------------------]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testRegressWithStep()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->start();
        $bar->advance(4);
        $bar->advance(4);
        $bar->advance(-2);

        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---------------------------]'.
            $this->generateOutput('    4 [---->-----------------------]').
            $this->generateOutput('    8 [-------->-------------------]').
            $this->generateOutput('    6 [------>---------------------]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testRegressMultipleTimes()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->start();
        $bar->advance(3);
        $bar->advance(3);
        $bar->advance(-1);
        $bar->advance(-2);

        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---------------------------]'.
            $this->generateOutput('    3 [--->------------------------]').
            $this->generateOutput('    6 [------>---------------------]').
            $this->generateOutput('    5 [----->----------------------]').
            $this->generateOutput('    3 [--->------------------------]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testRegressBelowMin()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 10, 0);
        $bar->setProgress(1);
        $bar->advance(-1);
        $bar->advance(-1);

        rewind($output->getStream());
        $this->assertEquals(
            '  1/10 [==>-------------------------]  10%'.
            $this->generateOutput('  0/10 [>---------------------------]   0%'),
            stream_get_contents($output->getStream())
        );
    }

    public function testFormat()
    {
        $expected =
            '  0/10 [>---------------------------]   0%'.
            $this->generateOutput(' 10/10 [============================] 100%')
        ;

        // max in construct, no format
        $bar = new ProgressBar($output = $this->getOutputStream(), 10, 0);
        $bar->start();
        $bar->advance(10);
        $bar->finish();

        rewind($output->getStream());
        $this->assertEquals($expected, stream_get_contents($output->getStream()));

        // max in start, no format
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->start(10);
        $bar->advance(10);
        $bar->finish();

        rewind($output->getStream());
        $this->assertEquals($expected, stream_get_contents($output->getStream()));

        // max in construct, explicit format before
        $bar = new ProgressBar($output = $this->getOutputStream(), 10, 0);
        $bar->setFormat(ProgressBar::FORMAT_NORMAL);
        $bar->start();
        $bar->advance(10);
        $bar->finish();

        rewind($output->getStream());
        $this->assertEquals($expected, stream_get_contents($output->getStream()));

        // max in start, explicit format before
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->setFormat(ProgressBar::FORMAT_NORMAL);
        $bar->start(10);
        $bar->advance(10);
        $bar->finish();

        rewind($output->getStream());
        $this->assertEquals($expected, stream_get_contents($output->getStream()));
    }

    public function testCustomizations()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 10, 0);
        $bar->setBarWidth(10);
        $bar->setBarCharacter('_');
        $bar->setEmptyBarCharacter(' ');
        $bar->setProgressCharacter('/');
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();
        $bar->advance();

        rewind($output->getStream());
        $this->assertEquals(
            '  0/10 [/         ]   0%'.
            $this->generateOutput('  1/10 [_/        ]  10%'),
            stream_get_contents($output->getStream())
        );
    }

    public function testDisplayWithoutStart()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 50, 0);
        $bar->display();

        rewind($output->getStream());
        $this->assertEquals(
            '  0/50 [>---------------------------]   0%',
            stream_get_contents($output->getStream())
        );
    }

    public function testDisplayWithQuietVerbosity()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(true, StreamOutput::VERBOSITY_QUIET), 50, 0);
        $bar->display();

        rewind($output->getStream());
        $this->assertEquals(
            '',
            stream_get_contents($output->getStream())
        );
    }

    public function testFinishWithoutStart()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 50, 0);
        $bar->finish();

        rewind($output->getStream());
        $this->assertEquals(
            ' 50/50 [============================] 100%',
            stream_get_contents($output->getStream())
        );
    }

    public function testPercent()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 50, 0);
        $bar->start();
        $bar->display();
        $bar->advance();
        $bar->advance();

        rewind($output->getStream());
        $this->assertEquals(
            '  0/50 [>---------------------------]   0%'.
            $this->generateOutput('  1/50 [>---------------------------]   2%').
            $this->generateOutput('  2/50 [=>--------------------------]   4%'),
            stream_get_contents($output->getStream())
        );
    }

    public function testOverwriteWithShorterLine()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 50, 0);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $bar->start();
        $bar->display();
        $bar->advance();

        // set shorter format
        $bar->setFormat(' %current%/%max% [%bar%]');
        $bar->advance();

        rewind($output->getStream());
        $this->assertEquals(
            '  0/50 [>---------------------------]   0%'.
            $this->generateOutput('  1/50 [>---------------------------]   2%').
            $this->generateOutput('  2/50 [=>--------------------------]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testOverwriteWithSectionOutput()
    {
        $sections = [];
        $stream = $this->getOutputStream(true);
        $output = new ConsoleSectionOutput($stream->getStream(), $sections, $stream->getVerbosity(), $stream->isDecorated(), new OutputFormatter());

        $bar = new ProgressBar($output, 50, 0);
        $bar->start();
        $bar->display();
        $bar->advance();
        $bar->advance();

        rewind($output->getStream());
        $this->assertEquals(
            '  0/50 [>---------------------------]   0%'.\PHP_EOL.
            "\x1b[1A\x1b[0J".'  1/50 [>---------------------------]   2%'.\PHP_EOL.
            "\x1b[1A\x1b[0J".'  2/50 [=>--------------------------]   4%'.\PHP_EOL,
            stream_get_contents($output->getStream())
        );
    }

    public function testOverwriteWithSectionOutputAndEol()
    {
        $sections = [];
        $stream = $this->getOutputStream(true);
        $output = new ConsoleSectionOutput($stream->getStream(), $sections, $stream->getVerbosity(), $stream->isDecorated(), new OutputFormatter());

        $bar = new ProgressBar($output, 50, 0);
        $bar->setFormat('[%bar%] %percent:3s%%' . PHP_EOL . '%message%' . PHP_EOL);
        $bar->setMessage('');
        $bar->start();
        $bar->display();
        $bar->setMessage('Doing something...');
        $bar->advance();
        $bar->setMessage('Doing something foo...');
        $bar->advance();

        rewind($output->getStream());
        $this->assertEquals(escapeshellcmd(
            '[>---------------------------]   0%'.\PHP_EOL.\PHP_EOL.
            "\x1b[2A\x1b[0J".'[>---------------------------]   2%'.\PHP_EOL. 'Doing something...' . \PHP_EOL .
            "\x1b[2A\x1b[0J".'[=>--------------------------]   4%'.\PHP_EOL. 'Doing something foo...' . \PHP_EOL),
            escapeshellcmd(stream_get_contents($output->getStream()))
        );
    }

    public function testOverwriteWithSectionOutputAndEolWithEmptyMessage()
    {
        $sections = [];
        $stream = $this->getOutputStream(true);
        $output = new ConsoleSectionOutput($stream->getStream(), $sections, $stream->getVerbosity(), $stream->isDecorated(), new OutputFormatter());

        $bar = new ProgressBar($output, 50, 0);
        $bar->setFormat('[%bar%] %percent:3s%%' . PHP_EOL . '%message%');
        $bar->setMessage('Start');
        $bar->start();
        $bar->display();
        $bar->setMessage('');
        $bar->advance();
        $bar->setMessage('Doing something...');
        $bar->advance();

        rewind($output->getStream());
        $this->assertEquals(escapeshellcmd(
            '[>---------------------------]   0%'.\PHP_EOL.'Start'.\PHP_EOL.
            "\x1b[2A\x1b[0J".'[>---------------------------]   2%'.\PHP_EOL .
            "\x1b[1A\x1b[0J".'[=>--------------------------]   4%'.\PHP_EOL. 'Doing something...' . \PHP_EOL),
            escapeshellcmd(stream_get_contents($output->getStream()))
        );
    }

    public function testOverwriteWithSectionOutputAndEolWithEmptyMessageComment()
    {
        $sections = [];
        $stream = $this->getOutputStream(true);
        $output = new ConsoleSectionOutput($stream->getStream(), $sections, $stream->getVerbosity(), $stream->isDecorated(), new OutputFormatter());

        $bar = new ProgressBar($output, 50, 0);
        $bar->setFormat('[%bar%] %percent:3s%%' . PHP_EOL . '<comment>%message%</comment>');
        $bar->setMessage('Start');
        $bar->start();
        $bar->display();
        $bar->setMessage('');
        $bar->advance();
        $bar->setMessage('Doing something...');
        $bar->advance();

        rewind($output->getStream());
        $this->assertEquals(escapeshellcmd(
            '[>---------------------------]   0%'.\PHP_EOL."\x1b[33mStart\x1b[39m".\PHP_EOL.
            "\x1b[2A\x1b[0J".'[>---------------------------]   2%'.\PHP_EOL .
            "\x1b[1A\x1b[0J".'[=>--------------------------]   4%'.\PHP_EOL. "\x1b[33mDoing something...\x1b[39m" . \PHP_EOL),
            escapeshellcmd(stream_get_contents($output->getStream()))
        );
    }

    public function testOverwriteWithAnsiSectionOutput()
    {
        // output has 43 visible characters plus 2 invisible ANSI characters
        putenv('COLUMNS=43');
        $sections = [];
        $stream = $this->getOutputStream(true);
        $output = new ConsoleSectionOutput($stream->getStream(), $sections, $stream->getVerbosity(), $stream->isDecorated(), new OutputFormatter());

        $bar = new ProgressBar($output, 50, 0);
        $bar->setFormat(" \033[44;37m%current%/%max%\033[0m [%bar%] %percent:3s%%");
        $bar->start();
        $bar->display();
        $bar->advance();
        $bar->advance();

        rewind($output->getStream());
        $this->assertSame(
            " \033[44;37m 0/50\033[0m [>---------------------------]   0%".\PHP_EOL.
            "\x1b[1A\x1b[0J \033[44;37m 1/50\033[0m [>---------------------------]   2%".\PHP_EOL.
            "\x1b[1A\x1b[0J \033[44;37m 2/50\033[0m [=>--------------------------]   4%".\PHP_EOL,
            stream_get_contents($output->getStream())
        );
        putenv('COLUMNS=120');
    }

    public function testOverwriteMultipleProgressBarsWithSectionOutputs()
    {
        $sections = [];
        $stream = $this->getOutputStream(true);
        $output1 = new ConsoleSectionOutput($stream->getStream(), $sections, $stream->getVerbosity(), $stream->isDecorated(), new OutputFormatter());
        $output2 = new ConsoleSectionOutput($stream->getStream(), $sections, $stream->getVerbosity(), $stream->isDecorated(), new OutputFormatter());

        $progress = new ProgressBar($output1, 50, 0);
        $progress2 = new ProgressBar($output2, 50, 0);

        $progress->start();
        $progress2->start();

        $progress2->advance();
        $progress->advance();

        rewind($stream->getStream());

        $this->assertEquals(
            '  0/50 [>---------------------------]   0%'.\PHP_EOL.
            '  0/50 [>---------------------------]   0%'.\PHP_EOL.
            "\x1b[1A\x1b[0J".'  1/50 [>---------------------------]   2%'.\PHP_EOL.
            "\x1b[2A\x1b[0J".'  1/50 [>---------------------------]   2%'.\PHP_EOL.
            "\x1b[1A\x1b[0J".'  1/50 [>---------------------------]   2%'.\PHP_EOL.
            '  1/50 [>---------------------------]   2%'.\PHP_EOL,
            stream_get_contents($stream->getStream())
        );
    }

    public function testOverwritWithNewlinesInMessage()
    {
        ProgressBar::setFormatDefinition('test', '%current%/%max% [%bar%] %percent:3s%% %message% Fruitcake marzipan toffee. Cupcake gummi bears tart dessert ice cream chupa chups cupcake chocolate bar sesame snaps. Croissant halvah cookie jujubes powder macaroon. Fruitcake bear claw bonbon jelly beans oat cake pie muffin Fruitcake marzipan toffee.');

        $bar = new ProgressBar($output = $this->getOutputStream(), 50, 0);
        $bar->setFormat('test');
        $bar->start();
        $bar->display();
        $bar->setMessage("Twas brillig, and the slithy toves. Did gyre and gimble in the wabe: All mimsy were the borogoves, And the mome raths outgrabe.\nBeware the Jabberwock, my son! The jaws that bite, the claws that catch! Beware the Jubjub bird, and shun The frumious Bandersnatch!");
        $bar->advance();
        $bar->setMessage("He took his vorpal sword in hand; Long time the manxome foe he sought— So rested he by the Tumtum tree And stood awhile in thought.\nAnd, as in uffish thought he stood, The Jabberwock, with eyes of flame, Came whiffling through the tulgey wood, And burbled as it came!");
        $bar->advance();

        rewind($output->getStream());
        $this->assertEquals(
            " 0/50 [>]   0% %message% Fruitcake marzipan toffee. Cupcake gummi bears tart dessert ice cream chupa chups cupcake chocolate bar sesame snaps. Croissant halvah cookie jujubes powder macaroon. Fruitcake bear claw bonbon jelly beans oat cake pie muffin Fruitcake marzipan toffee.\x1b[1G\x1b[2K 1/50 [>]   2% Twas brillig, and the slithy toves. Did gyre and gimble in the wabe: All mimsy were the borogoves, And the mome raths outgrabe.
Beware the Jabberwock, my son! The jaws that bite, the claws that catch! Beware the Jubjub bird, and shun The frumious Bandersnatch! Fruitcake marzipan toffee. Cupcake gummi bears tart dessert ice cream chupa chups cupcake chocolate bar sesame snaps. Croissant halvah cookie jujubes powder macaroon. Fruitcake bear claw bonbon jelly beans oat cake pie muffin Fruitcake marzipan toffee.\x1b[1G\x1b[2K\x1b[1A\x1b[1G\x1b[2K 2/50 [>]   4% He took his vorpal sword in hand; Long time the manxome foe he sought— So rested he by the Tumtum tree And stood awhile in thought.
And, as in uffish thought he stood, The Jabberwock, with eyes of flame, Came whiffling through the tulgey wood, And burbled as it came! Fruitcake marzipan toffee. Cupcake gummi bears tart dessert ice cream chupa chups cupcake chocolate bar sesame snaps. Croissant halvah cookie jujubes powder macaroon. Fruitcake bear claw bonbon jelly beans oat cake pie muffin Fruitcake marzipan toffee.",
            stream_get_contents($output->getStream())
        );
    }

    public function testOverwriteWithSectionOutputWithNewlinesInMessage()
    {
        $sections = [];
        $stream = $this->getOutputStream(true);
        $output = new ConsoleSectionOutput($stream->getStream(), $sections, $stream->getVerbosity(), $stream->isDecorated(), new OutputFormatter());

        ProgressBar::setFormatDefinition('test', '%current%/%max% [%bar%] %percent:3s%% %message% Fruitcake marzipan toffee. Cupcake gummi bears tart dessert ice cream chupa chups cupcake chocolate bar sesame snaps. Croissant halvah cookie jujubes powder macaroon. Fruitcake bear claw bonbon jelly beans oat cake pie muffin Fruitcake marzipan toffee.');

        $bar = new ProgressBar($output, 50, 0);
        $bar->setFormat('test');
        $bar->start();
        $bar->display();
        $bar->setMessage("Twas brillig, and the slithy toves. Did gyre and gimble in the wabe: All mimsy were the borogoves, And the mome raths outgrabe.\nBeware the Jabberwock, my son! The jaws that bite, the claws that catch! Beware the Jubjub bird, and shun The frumious Bandersnatch!");
        $bar->advance();
        $bar->setMessage("He took his vorpal sword in hand; Long time the manxome foe he sought— So rested he by the Tumtum tree And stood awhile in thought.\nAnd, as in uffish thought he stood, The Jabberwock, with eyes of flame, Came whiffling through the tulgey wood, And burbled as it came!");
        $bar->advance();

        rewind($output->getStream());
        $this->assertEquals(
            ' 0/50 [>]   0% %message% Fruitcake marzipan toffee. Cupcake gummi bears tart dessert ice cream chupa chups cupcake chocolate bar sesame snaps. Croissant halvah cookie jujubes powder macaroon. Fruitcake bear claw bonbon jelly beans oat cake pie muffin Fruitcake marzipan toffee.'.\PHP_EOL.
            "\x1b[3A\x1b[0J 1/50 [>]   2% Twas brillig, and the slithy toves. Did gyre and gimble in the wabe: All mimsy were the borogoves, And the mome raths outgrabe.
Beware the Jabberwock, my son! The jaws that bite, the claws that catch! Beware the Jubjub bird, and shun The frumious Bandersnatch! Fruitcake marzipan toffee. Cupcake gummi bears tart dessert ice cream chupa chups cupcake chocolate bar sesame snaps. Croissant halvah cookie jujubes powder macaroon. Fruitcake bear claw bonbon jelly beans oat cake pie muffin Fruitcake marzipan toffee.".\PHP_EOL.
            "\x1b[6A\x1b[0J 2/50 [>]   4% He took his vorpal sword in hand; Long time the manxome foe he sought— So rested he by the Tumtum tree And stood awhile in thought.
And, as in uffish thought he stood, The Jabberwock, with eyes of flame, Came whiffling through the tulgey wood, And burbled as it came! Fruitcake marzipan toffee. Cupcake gummi bears tart dessert ice cream chupa chups cupcake chocolate bar sesame snaps. Croissant halvah cookie jujubes powder macaroon. Fruitcake bear claw bonbon jelly beans oat cake pie muffin Fruitcake marzipan toffee.".\PHP_EOL,
            stream_get_contents($output->getStream())
        );
    }

    public function testMultipleSectionsWithCustomFormat()
    {
        $sections = [];
        $stream = $this->getOutputStream(true);
        $output1 = new ConsoleSectionOutput($stream->getStream(), $sections, $stream->getVerbosity(), $stream->isDecorated(), new OutputFormatter());
        $output2 = new ConsoleSectionOutput($stream->getStream(), $sections, $stream->getVerbosity(), $stream->isDecorated(), new OutputFormatter());

        ProgressBar::setFormatDefinition('test', '%current%/%max% [%bar%] %percent:3s%% Fruitcake marzipan toffee. Cupcake gummi bears tart dessert ice cream chupa chups cupcake chocolate bar sesame snaps. Croissant halvah cookie jujubes powder macaroon. Fruitcake bear claw bonbon jelly beans oat cake pie muffin Fruitcake marzipan toffee.');

        $progress = new ProgressBar($output1, 50, 0);
        $progress2 = new ProgressBar($output2, 50, 0);
        $progress2->setFormat('test');

        $progress->start();
        $progress2->start();

        $progress->advance();
        $progress2->advance();

        rewind($stream->getStream());

        $this->assertEquals('  0/50 [>---------------------------]   0%'.\PHP_EOL.
            ' 0/50 [>]   0% Fruitcake marzipan toffee. Cupcake gummi bears tart dessert ice cream chupa chups cupcake chocolate bar sesame snaps. Croissant halvah cookie jujubes powder macaroon. Fruitcake bear claw bonbon jelly beans oat cake pie muffin Fruitcake marzipan toffee.'.\PHP_EOL.
            "\x1b[4A\x1b[0J".' 0/50 [>]   0% Fruitcake marzipan toffee. Cupcake gummi bears tart dessert ice cream chupa chups cupcake chocolate bar sesame snaps. Croissant halvah cookie jujubes powder macaroon. Fruitcake bear claw bonbon jelly beans oat cake pie muffin Fruitcake marzipan toffee.'.\PHP_EOL.
            "\x1b[3A\x1b[0J".'  1/50 [>---------------------------]   2%'.\PHP_EOL.
            ' 0/50 [>]   0% Fruitcake marzipan toffee. Cupcake gummi bears tart dessert ice cream chupa chups cupcake chocolate bar sesame snaps. Croissant halvah cookie jujubes powder macaroon. Fruitcake bear claw bonbon jelly beans oat cake pie muffin Fruitcake marzipan toffee.'.\PHP_EOL.
            "\x1b[3A\x1b[0J".' 1/50 [>]   2% Fruitcake marzipan toffee. Cupcake gummi bears tart dessert ice cream chupa chups cupcake chocolate bar sesame snaps. Croissant halvah cookie jujubes powder macaroon. Fruitcake bear claw bonbon jelly beans oat cake pie muffin Fruitcake marzipan toffee.'.\PHP_EOL,
            stream_get_contents($stream->getStream())
        );
    }

    public function testStartWithMax()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->setFormat('%current%/%max% [%bar%]');
        $bar->start(50);
        $bar->advance();

        rewind($output->getStream());
        $this->assertEquals(
            ' 0/50 [>---------------------------]'.
            $this->generateOutput(' 1/50 [>---------------------------]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testSetCurrentProgress()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 50, 0);
        $bar->start();
        $bar->display();
        $bar->advance();
        $bar->setProgress(15);
        $bar->setProgress(25);

        rewind($output->getStream());
        $this->assertEquals(
            '  0/50 [>---------------------------]   0%'.
            $this->generateOutput('  1/50 [>---------------------------]   2%').
            $this->generateOutput(' 15/50 [========>-------------------]  30%').
            $this->generateOutput(' 25/50 [==============>-------------]  50%'),
            stream_get_contents($output->getStream())
        );
    }

    public function testSetCurrentBeforeStarting()
    {
        $bar = new ProgressBar($this->getOutputStream(), 0, 0);
        $bar->setProgress(15);
        $this->assertNotNull($bar->getStartTime());
    }

    public function testRedrawFrequency()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 6, 0);
        $bar->setRedrawFrequency(2);
        $bar->start();
        $bar->setProgress(1);
        $bar->advance(2);
        $bar->advance(2);
        $bar->advance(1);

        rewind($output->getStream());
        $this->assertEquals(
            ' 0/6 [>---------------------------]   0%'.
            $this->generateOutput(' 3/6 [==============>-------------]  50%').
            $this->generateOutput(' 5/6 [=======================>----]  83%').
            $this->generateOutput(' 6/6 [============================] 100%'),
            stream_get_contents($output->getStream())
        );
    }

    public function testRedrawFrequencyIsAtLeastOneIfZeroGiven()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->setRedrawFrequency(0);
        $bar->start();
        $bar->advance();

        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---------------------------]'.
            $this->generateOutput('    1 [->--------------------------]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testRedrawFrequencyIsAtLeastOneIfSmallerOneGiven()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->setRedrawFrequency(0);
        $bar->start();
        $bar->advance();

        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---------------------------]'.
            $this->generateOutput('    1 [->--------------------------]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testMultiByteSupport()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->start();
        $bar->setBarCharacter('■');
        $bar->advance(3);

        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---------------------------]'.
            $this->generateOutput('    3 [■■■>------------------------]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testClear()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 50, 0);
        $bar->start();
        $bar->setProgress(25);
        $bar->clear();

        rewind($output->getStream());
        $this->assertEquals(
            '  0/50 [>---------------------------]   0%'.
            $this->generateOutput(' 25/50 [==============>-------------]  50%').
            $this->generateOutput(''),
            stream_get_contents($output->getStream())
        );
    }

    public function testPercentNotHundredBeforeComplete()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 200, 0);
        $bar->start();
        $bar->display();
        $bar->advance(199);
        $bar->advance();

        rewind($output->getStream());
        $this->assertEquals(
            '   0/200 [>---------------------------]   0%'.
            $this->generateOutput(' 199/200 [===========================>]  99%').
            $this->generateOutput(' 200/200 [============================] 100%'),
            stream_get_contents($output->getStream())
        );
    }

    public function testNonDecoratedOutput()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(false), 200, 0);
        $bar->start();

        for ($i = 0; $i < 200; ++$i) {
            $bar->advance();
        }

        $bar->finish();

        rewind($output->getStream());
        $this->assertEquals(
            '   0/200 [>---------------------------]   0%'.\PHP_EOL.
            '  20/200 [==>-------------------------]  10%'.\PHP_EOL.
            '  40/200 [=====>----------------------]  20%'.\PHP_EOL.
            '  60/200 [========>-------------------]  30%'.\PHP_EOL.
            '  80/200 [===========>----------------]  40%'.\PHP_EOL.
            ' 100/200 [==============>-------------]  50%'.\PHP_EOL.
            ' 120/200 [================>-----------]  60%'.\PHP_EOL.
            ' 140/200 [===================>--------]  70%'.\PHP_EOL.
            ' 160/200 [======================>-----]  80%'.\PHP_EOL.
            ' 180/200 [=========================>--]  90%'.\PHP_EOL.
            ' 200/200 [============================] 100%',
            stream_get_contents($output->getStream())
        );
    }

    public function testNonDecoratedOutputWithClear()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(false), 50, 0);
        $bar->start();
        $bar->setProgress(25);
        $bar->clear();
        $bar->setProgress(50);
        $bar->finish();

        rewind($output->getStream());
        $this->assertEquals(
            '  0/50 [>---------------------------]   0%'.\PHP_EOL.
            ' 25/50 [==============>-------------]  50%'.\PHP_EOL.
            ' 50/50 [============================] 100%',
            stream_get_contents($output->getStream())
        );
    }

    public function testNonDecoratedOutputWithoutMax()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(false), 0, 0);
        $bar->start();
        $bar->advance();

        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---------------------------]'.\PHP_EOL.
            '    1 [->--------------------------]',
            stream_get_contents($output->getStream())
        );
    }

    public function testParallelBars()
    {
        $output = $this->getOutputStream();
        $bar1 = new ProgressBar($output, 2, 0);
        $bar2 = new ProgressBar($output, 3, 0);
        $bar2->setProgressCharacter('#');
        $bar3 = new ProgressBar($output, 0, 0);

        $bar1->start();
        $output->write("\n");
        $bar2->start();
        $output->write("\n");
        $bar3->start();

        for ($i = 1; $i <= 3; ++$i) {
            // up two lines
            $output->write("\033[2A");
            if ($i <= 2) {
                $bar1->advance();
            }
            $output->write("\n");
            $bar2->advance();
            $output->write("\n");
            $bar3->advance();
        }
        $output->write("\033[2A");
        $output->write("\n");
        $output->write("\n");
        $bar3->finish();

        rewind($output->getStream());
        $this->assertEquals(
            ' 0/2 [>---------------------------]   0%'."\n".
            ' 0/3 [#---------------------------]   0%'."\n".
            rtrim('    0 [>---------------------------]').

            "\033[2A".
            $this->generateOutput(' 1/2 [==============>-------------]  50%')."\n".
            $this->generateOutput(' 1/3 [=========#------------------]  33%')."\n".
            rtrim($this->generateOutput('    1 [->--------------------------]')).

            "\033[2A".
            $this->generateOutput(' 2/2 [============================] 100%')."\n".
            $this->generateOutput(' 2/3 [==================#---------]  66%')."\n".
            rtrim($this->generateOutput('    2 [-->-------------------------]')).

            "\033[2A".
            "\n".
            $this->generateOutput(' 3/3 [============================] 100%')."\n".
            rtrim($this->generateOutput('    3 [--->------------------------]')).

            "\033[2A".
            "\n".
            "\n".
            rtrim($this->generateOutput('    3 [============================]')),
            stream_get_contents($output->getStream())
        );
    }

    public function testWithoutMax()
    {
        $output = $this->getOutputStream();

        $bar = new ProgressBar($output, 0, 0);
        $bar->start();
        $bar->advance();
        $bar->advance();
        $bar->advance();
        $bar->finish();

        rewind($output->getStream());
        $this->assertEquals(
            rtrim('    0 [>---------------------------]').
            rtrim($this->generateOutput('    1 [->--------------------------]')).
            rtrim($this->generateOutput('    2 [-->-------------------------]')).
            rtrim($this->generateOutput('    3 [--->------------------------]')).
            rtrim($this->generateOutput('    3 [============================]')),
            stream_get_contents($output->getStream())
        );
    }

    public function testSettingMaxStepsDuringProgressing()
    {
        $output = $this->getOutputStream();
        $bar = new ProgressBar($output, 0, 0);
        $bar->start();
        $bar->setProgress(2);
        $bar->setMaxSteps(10);
        $bar->setProgress(5);
        $bar->setMaxSteps(100);
        $bar->setProgress(10);
        $bar->finish();

        rewind($output->getStream());
        $this->assertEquals(
            rtrim('    0 [>---------------------------]').
            rtrim($this->generateOutput('    2 [-->-------------------------]')).
            rtrim($this->generateOutput('  5/10 [==============>-------------]  50%')).
            rtrim($this->generateOutput('  10/100 [==>-------------------------]  10%')).
            rtrim($this->generateOutput(' 100/100 [============================] 100%')),
            stream_get_contents($output->getStream())
        );
    }

    public function testWithSmallScreen()
    {
        $output = $this->getOutputStream();

        $bar = new ProgressBar($output, 0, 0);
        putenv('COLUMNS=12');
        $bar->start();
        $bar->advance();
        putenv('COLUMNS=120');

        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---]'.
            $this->generateOutput('    1 [->--]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testAddingPlaceholderFormatter()
    {
        ProgressBar::setPlaceholderFormatterDefinition('remaining_steps', fn (ProgressBar $bar) => $bar->getMaxSteps() - $bar->getProgress());
        $bar = new ProgressBar($output = $this->getOutputStream(), 3, 0);
        $bar->setFormat(' %remaining_steps% [%bar%]');

        $bar->start();
        $bar->advance();
        $bar->finish();

        rewind($output->getStream());
        $this->assertEquals(
            ' 3 [>---------------------------]'.
            $this->generateOutput(' 2 [=========>------------------]').
            $this->generateOutput(' 0 [============================]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testAddingInstancePlaceholderFormatter()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 3, 0);
        $bar->setFormat(' %countdown% [%bar%]');
        $bar->setPlaceholderFormatter('countdown', $function = fn (ProgressBar $bar) => $bar->getMaxSteps() - $bar->getProgress());

        $this->assertSame($function, $bar->getPlaceholderFormatter('countdown'));

        $bar->start();
        $bar->advance();
        $bar->finish();

        rewind($output->getStream());
        $this->assertEquals(
            ' 3 [>---------------------------]'.
            $this->generateOutput(' 2 [=========>------------------]').
            $this->generateOutput(' 0 [============================]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testMultilineFormat()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 3, 0);
        $bar->setFormat("%bar%\nfoobar");

        $bar->start();
        $bar->advance();
        $bar->clear();
        $bar->finish();

        rewind($output->getStream());
        $this->assertEquals(
            ">---------------------------\nfoobar".
            $this->generateOutput("=========>------------------\nfoobar").
            "\x1B[1G\x1B[2K\x1B[1A".
            $this->generateOutput('').
            $this->generateOutput('============================').
            "\nfoobar",
            stream_get_contents($output->getStream())
        );
    }

    public function testAnsiColorsAndEmojis()
    {
        putenv('COLUMNS=156');

        $bar = new ProgressBar($output = $this->getOutputStream(), 15, 0);
        ProgressBar::setPlaceholderFormatterDefinition('memory', function (ProgressBar $bar) {
            static $i = 0;
            $mem = 100000 * $i;
            $colors = $i++ ? '41;37' : '44;37';

            return "\033[".$colors.'m '.Helper::formatMemory($mem)." \033[0m";
        });
        $bar->setFormat(" \033[44;37m %title:-37s% \033[0m\n %current%/%max% %bar% %percent:3s%%\n 🏁  %remaining:-10s% %memory:37s%");
        $bar->setBarCharacter($done = "\033[32m●\033[0m");
        $bar->setEmptyBarCharacter($empty = "\033[31m●\033[0m");
        $bar->setProgressCharacter($progress = "\033[32m➤ \033[0m");

        $bar->setMessage('Starting the demo... fingers crossed', 'title');
        $bar->start();

        rewind($output->getStream());
        $this->assertEquals(
            " \033[44;37m Starting the demo... fingers crossed  \033[0m\n".
            '  0/15 '.$progress.str_repeat($empty, 26)."   0%\n".
            " \xf0\x9f\x8f\x81  < 1 ms                         \033[44;37m 0 B \033[0m",
            stream_get_contents($output->getStream())
        );
        ftruncate($output->getStream(), 0);
        rewind($output->getStream());

        $bar->setMessage('Looks good to me...', 'title');
        $bar->advance(4);

        rewind($output->getStream());
        $this->assertEquals(
            $this->generateOutput(
                " \033[44;37m Looks good to me...                   \033[0m\n".
                '  4/15 '.str_repeat($done, 7).$progress.str_repeat($empty, 19)."  26%\n".
                " \xf0\x9f\x8f\x81  < 1 ms                      \033[41;37m 97 KiB \033[0m"
            ),
            stream_get_contents($output->getStream())
        );
        ftruncate($output->getStream(), 0);
        rewind($output->getStream());

        $bar->setMessage('Thanks, bye', 'title');
        $bar->finish();

        rewind($output->getStream());
        $this->assertEquals(
            $this->generateOutput(
                " \033[44;37m Thanks, bye                           \033[0m\n".
                ' 15/15 '.str_repeat($done, 28)." 100%\n".
                " \xf0\x9f\x8f\x81  < 1 ms                     \033[41;37m 195 KiB \033[0m"
            ),
            stream_get_contents($output->getStream())
        );
        putenv('COLUMNS=120');
    }

    public function testSetFormat()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->setFormat(ProgressBar::FORMAT_NORMAL);
        $bar->start();
        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---------------------------]',
            stream_get_contents($output->getStream())
        );

        $bar = new ProgressBar($output = $this->getOutputStream(), 10, 0);
        $bar->setFormat(ProgressBar::FORMAT_NORMAL);
        $bar->start();
        rewind($output->getStream());
        $this->assertEquals(
            '  0/10 [>---------------------------]   0%',
            stream_get_contents($output->getStream())
        );
    }

    public function testSetFormatWithTimes()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 15, 0);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%/%remaining:-6s%');
        $bar->start();
        rewind($output->getStream());
        $this->assertEquals(
            ' 0/15 [>---------------------------]   0% < 1 ms/< 1 ms/< 1 ms',
            stream_get_contents($output->getStream())
        );
    }

    public function testUnicode()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 10, 0);
        ProgressBar::setFormatDefinition('test', '%current%/%max% [%bar%] %percent:3s%% %message% Fruitcake marzipan toffee. Cupcake gummi bears tart dessert ice cream chupa chups cupcake chocolate bar sesame snaps. Croissant halvah cookie jujubes powder macaroon. Fruitcake bear claw bonbon jelly beans oat cake pie muffin Fruitcake marzipan toffee.');
        $bar->setFormat('test');
        $bar->setProgressCharacter('💧');
        $bar->start();
        rewind($output->getStream());
        $this->assertStringContainsString(
            ' 0/10 [💧]   0%',
            stream_get_contents($output->getStream())
        );
        $bar->finish();
    }

    /**
     * @dataProvider provideFormat
     */
    public function testFormatsWithoutMax($format)
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->setFormat($format);
        $bar->start();

        rewind($output->getStream());
        $this->assertNotEmpty(stream_get_contents($output->getStream()));
    }

    /**
     * Provides each defined format.
     */
    public static function provideFormat(): array
    {
        return [
            ['normal'],
            ['verbose'],
            ['very_verbose'],
            ['debug'],
        ];
    }

    public function testIterate()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);

        $this->assertEquals([1, 2], iterator_to_array($bar->iterate([1, 2])));

        rewind($output->getStream());
        $this->assertEquals(
            ' 0/2 [>---------------------------]   0%'.
            $this->generateOutput(' 1/2 [==============>-------------]  50%').
            $this->generateOutput(' 2/2 [============================] 100%'),
            stream_get_contents($output->getStream())
        );
    }

    public function testIterateUncountable()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);

        $this->assertEquals([1, 2], iterator_to_array($bar->iterate((function () {
            yield 1;
            yield 2;
        })())));

        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---------------------------]'.
            $this->generateOutput('    1 [->--------------------------]').
            $this->generateOutput('    2 [-->-------------------------]').
            $this->generateOutput('    2 [============================]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testEmptyInputWithDebugFormat()
    {
        $bar = new ProgressBar($output = $this->getOutputStream());
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');

        $this->assertEquals([], iterator_to_array($bar->iterate([])));

        rewind($output->getStream());
        $this->assertEquals(
            ' 0/0 [============================] 100% < 1 ms/< 1 ms',
            stream_get_contents($output->getStream())
        );
    }

    protected function getOutputStream($decorated = true, $verbosity = StreamOutput::VERBOSITY_NORMAL)
    {
        return new StreamOutput(fopen('php://memory', 'r+', false), $verbosity, $decorated);
    }

    protected function generateOutput($expected)
    {
        $count = substr_count($expected, "\n");

        return ($count ? str_repeat("\x1B[1G\x1b[2K\x1B[1A", $count) : '')."\x1B[1G\x1B[2K".$expected;
    }

    public function testBarWidthWithMultilineFormat()
    {
        putenv('COLUMNS=10');

        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->setFormat("%bar%\n0123456789");

        // before starting
        $bar->setBarWidth(5);
        $this->assertEquals(5, $bar->getBarWidth());

        // after starting
        $bar->start();
        rewind($output->getStream());
        $this->assertEquals(5, $bar->getBarWidth(), stream_get_contents($output->getStream()));
        putenv('COLUMNS=120');
    }

    public function testMinAndMaxSecondsBetweenRedraws()
    {
        $bar = new ProgressBar($output = $this->getOutputStream());
        $bar->setRedrawFrequency(1);
        $bar->minSecondsBetweenRedraws(5);
        $bar->maxSecondsBetweenRedraws(10);

        $bar->start();
        $bar->setProgress(1);
        sleep(10);
        $bar->setProgress(2);
        sleep(20);
        $bar->setProgress(3);

        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---------------------------]'.
            $this->generateOutput('    2 [-->-------------------------]').
            $this->generateOutput('    3 [--->------------------------]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testMaxSecondsBetweenRedraws()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->setRedrawFrequency(4); // disable step based redraws
        $bar->start();

        $bar->setProgress(1); // No threshold hit, no redraw
        $bar->maxSecondsBetweenRedraws(2);
        sleep(1);
        $bar->setProgress(2); // Still no redraw because it takes 2 seconds for a redraw
        sleep(1);
        $bar->setProgress(3); // 1+1 = 2 -> redraw finally
        $bar->setProgress(4); // step based redraw freq hit, redraw even without sleep
        $bar->setProgress(5); // No threshold hit, no redraw
        $bar->maxSecondsBetweenRedraws(3);
        sleep(2);
        $bar->setProgress(6); // No redraw even though 2 seconds passed. Throttling has priority
        $bar->maxSecondsBetweenRedraws(2);
        $bar->setProgress(7); // Throttling relaxed, draw

        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---------------------------]'.
            $this->generateOutput('    3 [--->------------------------]').
            $this->generateOutput('    4 [---->-----------------------]').
            $this->generateOutput('    7 [------->--------------------]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testMinSecondsBetweenRedraws()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 0, 0);
        $bar->setRedrawFrequency(1);
        $bar->minSecondsBetweenRedraws(1);
        $bar->start();
        $bar->setProgress(1); // Too fast, should not draw
        sleep(1);
        $bar->setProgress(2); // 1 second passed, draw
        $bar->minSecondsBetweenRedraws(2);
        sleep(1);
        $bar->setProgress(3); // 1 second passed but we changed threshold, should not draw
        sleep(1);
        $bar->setProgress(4); // 1+1 seconds = 2 seconds passed which conforms threshold, draw
        $bar->setProgress(5); // No threshold hit, no redraw

        rewind($output->getStream());
        $this->assertEquals(
            '    0 [>---------------------------]'.
            $this->generateOutput('    2 [-->-------------------------]').
            $this->generateOutput('    4 [---->-----------------------]'),
            stream_get_contents($output->getStream())
        );
    }

    public function testNoWriteWhenMessageIsSame()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 2);
        $bar->start();
        $bar->advance();
        $bar->display();
        rewind($output->getStream());
        $this->assertEquals(
            ' 0/2 [>---------------------------]   0%'.
            $this->generateOutput(' 1/2 [==============>-------------]  50%'),
            stream_get_contents($output->getStream())
        );
    }

    public function testMultiLineFormatIsFullyCleared()
    {
        $bar = new ProgressBar($output = $this->getOutputStream(), 3);
        $bar->setFormat("%current%/%max%\n%message%\nFoo");

        $bar->setMessage('1234567890');
        $bar->start();
        $bar->display();

        $bar->setMessage('ABC');
        $bar->advance();
        $bar->display();

        $bar->setMessage('A');
        $bar->advance();
        $bar->display();

        $bar->finish();

        rewind($output->getStream());
        $this->assertEquals(
            "0/3\n1234567890\nFoo".
            $this->generateOutput("1/3\nABC\nFoo").
            $this->generateOutput("2/3\nA\nFoo").
            $this->generateOutput("3/3\nA\nFoo"),
            stream_get_contents($output->getStream())
        );
    }

    public function testMultiLineFormatIsFullyCorrectlyWithManuallyCleanup()
    {
        ProgressBar::setFormatDefinition('normal_nomax', "[%bar%]\n%message%");
        $bar = new ProgressBar($output = $this->getOutputStream());
        $bar->setMessage('Processing "foobar"...');
        $bar->start();
        $bar->clear();
        $output->writeln('Foo!');
        $bar->display();
        $bar->finish();

        rewind($output->getStream());
        $this->assertEquals(
            "[>---------------------------]\n".
            'Processing "foobar"...'.
            "\x1B[1G\x1B[2K\x1B[1A".
            $this->generateOutput('').
            'Foo!'.\PHP_EOL.
            $this->generateOutput('[--->------------------------]').
            "\nProcessing \"foobar\"...".
            $this->generateOutput("[============================]\nProcessing \"foobar\"..."),
            stream_get_contents($output->getStream())
        );
    }

    public function testGetNotSetMessage()
    {
        $progressBar = new ProgressBar($this->getOutputStream());

        $this->assertNull($progressBar->getMessage());
    }
}
