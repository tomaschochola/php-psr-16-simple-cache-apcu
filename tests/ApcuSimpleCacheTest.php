<?php

/**
 * @author Tomáš Chochola <tomaschochola@tomaschochola.cz>
 * @copyright © 2026 Tomáš Chochola <tomaschochola@tomaschochola.cz>
 *
 * @license CC-BY-ND-4.0
 *
 * @see {@link https://creativecommons.org/licenses/by-nd/4.0/} License
 * @see {@link https://github.com/tomaschochola} GitHub Profile
 * @see {@link https://github.com/sponsors/tomaschochola} GitHub Sponsors
 */

declare(strict_types=1);

namespace Tests;

use DateInterval;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;
use TomasChochola\Psr\SimpleCache\ApcuSimpleCache;

use function iterator_to_array;

/**
 * @internal
 *
 * @no-named-arguments
 */
#[CoversClass(ApcuSimpleCache::class)]
#[Small()]
final class ApcuSimpleCacheTest extends TestCase
{
    #[Test()]
    public function batchDeletionTreatsMissingKeysAsSuccessfullyDeleted(): void
    {
        $cache = new ApcuSimpleCache();

        self::assertTrue($cache->clear());
        self::assertTrue($cache->set('later', 'value'));
        self::assertTrue($cache->deleteMultiple(['missing', 'later']));
        self::assertFalse($cache->has('later'));
    }

    #[Test()]
    public function multipleOperationsPreserveDefaultsAndNullValues(): void
    {
        $cache = new ApcuSimpleCache();

        self::assertTrue($cache->clear());
        self::assertSame(['missing' => 'default'], iterator_to_array($cache->getMultiple(['missing'], 'default')));
        self::assertTrue($cache->setMultiple([]));
        self::assertTrue($cache->deleteMultiple([]));

        self::assertTrue($cache->setMultiple([
            'first' => 'value',
            'second' => null,
            '7' => 'numeric-string-key',
        ], 60));

        self::assertSame('numeric-string-key', $cache->get('7'));

        self::assertSame([
            'first' => 'value',
            'second' => null,
            'missing' => 'default',
        ], iterator_to_array($cache->getMultiple(['first', 'second', 'missing'], 'default')));

        self::assertTrue($cache->deleteMultiple(['first', 'second', '7']));
        self::assertFalse($cache->has('first'));
        self::assertFalse($cache->has('second'));
    }

    #[Test()]
    public function rejectsInvalidKeys(): void
    {
        $this->expectException(PsrInvalidArgumentException::class);

        self::assertNull((new ApcuSimpleCache())->get('invalid:key'));
    }

    #[Test()]
    public function storesRetrievesAndDeletesValuesIncludingNull(): void
    {
        $cache = new ApcuSimpleCache();

        self::assertTrue($cache->clear());
        self::assertSame('default', $cache->get('missing', 'default'));
        self::assertFalse($cache->has('value'));
        self::assertTrue($cache->set('value', 'stored'));
        self::assertTrue($cache->set('null', null, new DateInterval('PT1M')));
        self::assertSame('stored', $cache->get('value'));
        self::assertNull($cache->get('null', 'default'));
        self::assertTrue($cache->has('value'));
        self::assertTrue($cache->delete('value'));
        self::assertFalse($cache->has('value'));
    }

    #[Test()]
    public function zeroAndNegativeTimeToLiveDeleteExistingValues(): void
    {
        $cache = new ApcuSimpleCache();
        $negativeInterval = new DateInterval('PT1S');
        $negativeInterval->invert = 1;

        self::assertTrue($cache->clear());
        self::assertTrue($cache->set('zero', 'stored'));
        self::assertTrue($cache->set('zero', 'replacement', 0));
        self::assertFalse($cache->has('zero'));
        self::assertTrue($cache->set('negative', 'stored'));
        self::assertTrue($cache->set('negative', 'replacement', -1));
        self::assertFalse($cache->has('negative'));
        self::assertTrue($cache->set('interval', 'stored'));
        self::assertTrue($cache->set('interval', 'replacement', $negativeInterval));
        self::assertFalse($cache->has('interval'));
        self::assertTrue($cache->set('persistent', 'stored', null));
        self::assertSame('stored', $cache->get('persistent'));
    }
}
