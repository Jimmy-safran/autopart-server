<?php

namespace LaminasTest\Code\Scanner;

use Laminas\Code\Scanner\DocBlockScanner;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function str_replace;

#[Group('Laminas_Code_Scanner')]
class DocBlockScannerTest extends TestCase
{
    #[Group('Laminas-110')]
    public function testDocBlockScannerParsesTagsWithNoValuesProperly()
    {
        $docComment   = <<<EOB
/**
 * @mytag
 */
EOB;
        $tokenScanner = new DocBlockScanner($docComment);
        $tags         = $tokenScanner->getTags();
        self::assertCount(1, $tags);
        self::assertArrayHasKey('name', $tags[0]);
        self::assertEquals('@mytag', $tags[0]['name']);
        self::assertArrayHasKey('value', $tags[0]);
        self::assertEquals('', $tags[0]['value']);
    }

    public function testDocBlockScannerDescriptions()
    {
        $docComment   = <<<EOB
/**
 * Short Description
 *
 * Long Description
 * continued in the second line
 */
EOB;
        $tokenScanner = new DocBlockScanner($docComment);
        self::assertEquals('Short Description', $tokenScanner->getShortDescription());
        self::assertEquals('Long Description continued in the second line', $tokenScanner->getLongDescription());

        // windows-style line separators
        $docComment   = str_replace("\n", "\r\n", $docComment);
        $tokenScanner = new DocBlockScanner($docComment);
        self::assertEquals('Short Description', $tokenScanner->getShortDescription());
        self::assertEquals('Long Description continued in the second line', $tokenScanner->getLongDescription());
    }

    public function testInvalidDocBlock()
    {
        $docComment   = '/**';
        $tokenScanner = new DocBlockScanner($docComment);
        self::assertEquals('', $tokenScanner->getShortDescription());
    }
}
