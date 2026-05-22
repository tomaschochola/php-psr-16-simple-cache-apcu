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

namespace TomasChochola\Psr\SimpleCache;

use DateInterval;
use DateTimeImmutable;
use NoDiscard;
use Override;
use Psr\SimpleCache\CacheInterface;

use function apcu_clear_cache;
use function apcu_delete;
use function apcu_exists;
use function apcu_fetch;
use function apcu_store;
use function array_key_exists;
use function assert;
use function is_array;
use function is_int;
use function is_string;
use function iterator_to_array;

/**
 * @no-named-arguments
 */
readonly class ApcuSimpleCache implements CacheInterface
{
    #[NoDiscard()]
    #[Override()]
    public function clear(): bool
    {
        return apcu_clear_cache();
    }

    #[NoDiscard()]
    #[Override()]
    public function delete(string $key): bool
    {
        return apcu_delete($key);
    }

    #[NoDiscard()]
    #[Override()]
    public function deleteMultiple(iterable $keys): bool
    {
        $ok = true;

        foreach ($keys as $key) {
            $ok = $ok && $this->delete((string) $key);
        }

        return $ok;
    }

    #[NoDiscard()]
    #[Override()]
    public function get(string $key, mixed $default = null): mixed
    {
        $ok = false;
        $value = apcu_fetch($key, $ok);

        if ($ok) {
            return $value;
        }

        return $default;
    }

    #[NoDiscard()]
    #[Override()]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $arr = iterator_to_array($keys);
        $ok = false;
        $values = apcu_fetch($arr, $ok);

        if ($ok) {
            assert(is_array($values));

            foreach ($arr as $key) {
                $value = $values[$key] ?? null;

                if ($value !== null || array_key_exists($key, $values)) {
                    yield $key => $value;
                } else {
                    yield $key => $default;
                }
            }
        } else {
            foreach ($arr as $key) {
                yield $key => $default;
            }
        }
    }

    #[NoDiscard()]
    #[Override()]
    public function has(string $key): bool
    {
        return apcu_exists($key);
    }

    #[NoDiscard()]
    #[Override()]
    public function set(string $key, mixed $value, DateInterval | int | null $ttl = null): bool
    {
        return apcu_store($key, $value, $ttl instanceof DateInterval ? self::getInterval($ttl) : ($ttl ?? 0));
    }

    /**
     * @param iterable<mixed, mixed> $values
     */
    #[NoDiscard()]
    #[Override()]
    public function setMultiple(iterable $values, DateInterval | int | null $ttl = null): bool
    {
        $ok = true;

        foreach ($values as $key => $value) {
            assert(is_string($key) || is_int($key));

            $ok = $ok && $this->set((string) $key, $value, $ttl);
        }

        return $ok;
    }

    #[NoDiscard()]
    private static function getInterval(DateInterval $interval): int
    {
        $now = new DateTimeImmutable();

        return $now->add($interval)->getTimestamp() - $now->getTimestamp();
    }
}
