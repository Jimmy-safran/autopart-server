<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\String\Tests\Inflector;

use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Inflector\EnglishInflector;

class EnglishInflectorTest extends TestCase
{
    public static function singularizeProvider()
    {
        // see http://english-zone.com/spelling/plurals.html
        // see http://www.scribd.com/doc/3271143/List-of-100-Irregular-Plural-Nouns-in-English
        return [
            ['accesses', 'access'],
            ['addresses', 'address'],
            ['agendas', 'agenda'],
            ['albums', 'album'],
            ['alumnae', 'alumna'],
            ['alumni', 'alumnus'],
            ['analyses', ['analys', 'analyse', 'analysis']],
            ['ankles', 'ankle'],
            ['antennae', 'antenna'],
            ['antennas', 'antenna'],
            ['appendices', ['appendex', 'appendix', 'appendice']],
            ['arches', ['arch', 'arche']],
            ['articles', 'article'],
            ['atlases', ['atlas', 'atlase', 'atlasis']],
            ['axes', ['ax', 'axe', 'axis']],
            ['babies', 'baby'],
            ['bacteria', 'bacterium'],
            ['bases', ['bas', 'base', 'basis']],
            ['batches', ['batch', 'batche']],
            ['beaux', 'beau'],
            ['bees', 'bee'],
            ['boxes', 'box'],
            ['boys', 'boy'],
            ['bureaus', 'bureau'],
            ['bureaux', 'bureau'],
            ['buses', ['bus', 'buse', 'busis']],
            ['bushes', ['bush', 'bushe']],
            ['buttons', 'button'],
            ['calves', ['calf', 'calve', 'calff']],
            ['cars', 'car'],
            ['cassettes', ['cassett', 'cassette']],
            ['caves', ['caf', 'cave', 'caff']],
            ['chateaux', 'chateau'],
            ['cheeses', ['chees', 'cheese', 'cheesis']],
            ['children', 'child'],
            ['circuses', ['circus', 'circuse', 'circusis']],
            ['cliffs', 'cliff'],
            ['codes', 'code'],
            ['committee', 'committee'],
            ['corpora', 'corpus'],
            ['coupons', 'coupon'],
            ['crises', ['cris', 'crise', 'crisis']],
            ['criteria', 'criterion'],
            ['cups', 'cup'],
            ['curricula', 'curriculum'],
            ['data', 'data'],
            ['days', 'day'],
            ['discos', 'disco'],
            ['devices', ['devex', 'devix', 'device']],
            ['drives', 'drive'],
            ['drivers', 'driver'],
            ['dwarves', ['dwarf', 'dwarve', 'dwarff']],
            ['echoes', ['echo', 'echoe']],
            ['edges', 'edge'],
            ['elves', ['elf', 'elve', 'elff']],
            ['emphases', ['emphas', 'emphase', 'emphasis']],
            ['employees', 'employee'],
            ['faxes', 'fax'],
            ['fees', 'fee'],
            ['feet', 'foot'],
            ['feedback', 'feedback'],
            ['foci', 'focus'],
            ['focuses', ['focus', 'focuse', 'focusis']],
            ['formulae', 'formula'],
            ['formulas', 'formula'],
            ['conspectuses', 'conspectus'],
            ['fungi', 'fungus'],
            ['funguses', ['fungus', 'funguse', 'fungusis']],
            ['garages', ['garag', 'garage']],
            ['geese', 'goose'],
            ['genera', 'genus'],
            ['halves', ['half', 'halve', 'halff']],
            ['hats', 'hat'],
            ['heroes', ['hero', 'heroe']],
            ['hippopotamuses', ['hippopotamus', 'hippopotamuse', 'hippopotamusis']], // hippopotami
            ['hoaxes', 'hoax'],
            ['hooves', ['hoof', 'hoove', 'hooff']],
            ['houses', ['hous', 'house', 'housis']],
            ['indexes', 'index'],
            ['indices', ['index', 'indix', 'indice']],
            ['ions', 'ion'],
            ['irises', ['iris', 'irise', 'irisis']],
            ['kisses', 'kiss'],
            ['knives', 'knife'],
            ['lamps', 'lamp'],
            ['lessons', 'lesson'],
            ['leaves', ['leaf', 'leave', 'leaff']],
            ['lice', 'louse'],
            ['lives', 'life'],
            ['matrices', ['matrex', 'matrix', 'matrice']],
            ['matrixes', 'matrix'],
            ['media', 'medium'],
            ['memoranda', 'memorandum'],
            ['men', 'man'],
            ['mice', 'mouse'],
            ['moves', 'move'],
            ['movies', 'movie'],
            ['names', 'name'],
            ['nebulae', 'nebula'],
            ['neuroses', ['neuros', 'neurose', 'neurosis']],
            ['news', 'news'],
            ['oases', ['oas', 'oase', 'oasis']],
            ['objectives', 'objective'],
            ['oxen', 'ox'],
            ['parties', 'party'],
            ['people', 'person'],
            ['persons', 'person'],
            ['phenomena', 'phenomenon'],
            ['photos', 'photo'],
            ['pianos', 'piano'],
            ['plateaux', 'plateau'],
            ['poisons', 'poison'],
            ['poppies', 'poppy'],
            ['prices', ['prex', 'prix', 'price']],
            ['quizzes', 'quiz'],
            ['quora', 'quorum'],
            ['quorums', 'quorum'],
            ['radii', 'radius'],
            ['roofs', 'roof'],
            ['roses', ['ros', 'rose', 'rosis']],
            ['sandwiches', ['sandwich', 'sandwiche']],
            ['scarves', ['scarf', 'scarve', 'scarff']],
            ['schemas', 'schema'], // schemata
            ['seasons', 'season'],
            ['selfies', 'selfie'],
            ['series', 'series'],
            ['services', 'service'],
            ['sheriffs', 'sheriff'],
            ['shoes', ['sho', 'shoe']],
            ['species', 'species'],
            ['spies', 'spy'],
            ['staves', ['staf', 'stave', 'staff']],
            ['status', 'status'],
            ['statuses', 'status'],
            ['stories', 'story'],
            ['strata', 'stratum'],
            ['suitcases', ['suitcas', 'suitcase', 'suitcasis']],
            ['syllabi', 'syllabus'],
            ['tags', 'tag'],
            ['teeth', 'tooth'],
            ['theses', ['thes', 'these', 'thesis']],
            ['thieves', ['thief', 'thieve', 'thieff']],
            ['treasons', 'treason'],
            ['trees', 'tree'],
            ['waltzes', ['waltz', 'waltze']],
            ['wives', 'wife'],
            ['zombies', 'zombie'],

            // test casing: if the first letter was uppercase, it should remain so
            ['Men', 'Man'],
            ['GrandChildren', 'GrandChild'],
            ['SubTrees', 'SubTree'],

            // Known issues
            // ['insignia', 'insigne'],
            // ['insignias', 'insigne'],
            // ['rattles', 'rattle'],
        ];
    }

    public static function pluralizeProvider()
    {
        // see http://english-zone.com/spelling/plurals.html
        // see http://www.scribd.com/doc/3271143/List-of-100-Irregular-Plural-Nouns-in-English
        return [
            ['access', 'accesses'],
            ['address', 'addresses'],
            ['agenda', 'agendas'],
            ['aircraft', 'aircraft'],
            ['album', 'albums'],
            ['alumnus', 'alumni'],
            ['analysis', 'analyses'],
            ['ankle', 'ankles'],
            ['antenna', 'antennas'], // antennae
            ['appendix', ['appendices', 'appendixes']],
            ['arch', 'arches'],
            ['article', 'articles'],
            ['atlas', 'atlases'],
            ['axe', 'axes'],
            ['axis', 'axes'],
            ['baby', 'babies'],
            ['bacterium', 'bacteria'],
            ['base', 'bases'],
            ['batch', 'batches'],
            ['beau', ['beaus', 'beaux']],
            ['bee', 'bees'],
            ['box', 'boxes'],
            ['boy', 'boys'],
            ['bureau', ['bureaus', 'bureaux']],
            ['bus', 'buses'],
            ['bush', 'bushes'],
            ['button', 'buttons'],
            ['calf', ['calfs', 'calves']],
            ['campus', 'campuses'],
            ['car', 'cars'],
            ['cassette', 'cassettes'],
            ['cave', 'caves'],
            ['chateau', ['chateaus', 'chateaux']],
            ['cheese', 'cheeses'],
            ['child', 'children'],
            ['circus', 'circuses'],
            ['cliff', 'cliffs'],
            ['committee', 'committees'],
            ['coupon', 'coupons'],
            ['crisis', 'crises'],
            ['criterion', 'criteria'],
            ['cup', 'cups'],
            ['curriculum', 'curricula'],
            ['data', 'data'],
            ['day', 'days'],
            ['disco', 'discos'],
            ['device', 'devices'],
            ['drive', 'drives'],
            ['driver', 'drivers'],
            ['dwarf', ['dwarfs', 'dwarves']],
            ['echo', 'echoes'],
            ['edge', 'edges'],
            ['elf', ['elfs', 'elves']],
            ['emphasis', 'emphases'],
            ['fax', ['faxes', 'faxxes']],
            ['feedback', 'feedback'],
            ['focus', 'focuses'],
            ['foot', 'feet'],
            ['formula', 'formulas'], // formulae
            ['conspectus', 'conspectuses'],
            ['fungus', 'fungi'],
            ['garage', 'garages'],
            ['goose', 'geese'],
            ['half', ['halfs', 'halves']],
            ['hat', 'hats'],
            ['hero', 'heroes'],
            ['hippocampus', 'hippocampi'],
            ['hippopotamus', 'hippopotami'], // hippopotamuses
            ['hoax', 'hoaxes'],
            ['hoof', ['hoofs', 'hooves']],
            ['house', 'houses'],
            ['icon', 'icons'],
            ['index', ['indicies', 'indexes']],
            ['ion', 'ions'],
            ['iris', 'irises'],
            ['kiss', 'kisses'],
            ['knife', 'knives'],
            ['lamp', 'lamps'],
            ['leaf', ['leafs', 'leaves']],
            ['lesson', 'lessons'],
            ['life', 'lives'],
            ['louse', 'lice'],
            ['man', 'men'],
            ['matrix', ['matrices', 'matrixes']],
            ['medium', 'media'],
            ['memorandum', 'memoranda'],
            ['mouse', 'mice'],
            ['move', 'moves'],
            ['movie', 'movies'],
            ['nebula', 'nebulae'],
            ['neurosis', 'neuroses'],
            ['news', 'news'],
            ['oasis', 'oases'],
            ['objective', 'objectives'],
            ['ox', 'oxen'],
            ['party', 'parties'],
            ['person', ['persons', 'people']],
            ['phenomenon', 'phenomena'],
            ['photo', 'photos'],
            ['piano', 'pianos'],
            ['plateau', ['plateaus', 'plateaux']],
            ['poison', 'poisons'],
            ['poppy', 'poppies'],
            ['price', 'prices'],
            ['quiz', 'quizzes'],
            ['quorum', ['quora', 'quorums']],
            ['radius', 'radii'],
            ['roof', ['roofs', 'rooves']],
            ['rose', 'roses'],
            ['sandwich', 'sandwiches'],
            ['scarf', ['scarfs', 'scarves']],
            ['schema', 'schemas'], // schemata
            ['season', 'seasons'],
            ['selfie', 'selfies'],
            ['series', 'series'],
            ['service', 'services'],
            ['sheriff', 'sheriffs'],
            ['shoe', 'shoes'],
            ['species', 'species'],
            ['status', ['status', 'statuses']],
            ['stratum', 'strata'],
            ['spy', 'spies'],
            ['staff', 'staves'],
            ['story', 'stories'],
            ['stratum', 'strata'],
            ['suitcase', 'suitcases'],
            ['syllabus', 'syllabi'],
            ['tag', 'tags'],
            ['thief', ['thiefs', 'thieves']],
            ['tooth', 'teeth'],
            ['treason', 'treasons'],
            ['tree', 'trees'],
            ['waltz', 'waltzes'],
            ['wife', 'wives'],
            ['icon', 'icons'],
            ['hippocampus', 'hippocampi'],
            ['campus', 'campuses'],
            ['hardware', 'hardware'],
            ['alias', 'aliases'],

            // test casing: if the first letter was uppercase, it should remain so
            ['Man', 'Men'],
            ['GrandChild', 'GrandChildren'],
            ['SubTree', 'SubTrees'],
        ];
    }

    /**
     * @dataProvider singularizeProvider
     */
    public function testSingularize(string $plural, $singular)
    {
        $this->assertSame(\is_array($singular) ? $singular : [$singular], (new EnglishInflector())->singularize($plural));
    }

    /**
     * @dataProvider pluralizeProvider
     */
    public function testPluralize(string $singular, $plural)
    {
        $this->assertSame(\is_array($plural) ? $plural : [$plural], (new EnglishInflector())->pluralize($singular));
    }

    public function testPluralizeEmptyString()
    {
        $plural = (new EnglishInflector())->pluralize('');
        $this->assertSame([''], $plural);
    }

    public function testSingularizeEmptyString()
    {
        $singular = (new EnglishInflector())->singularize('');
        $this->assertSame([''], $singular);
    }
}
