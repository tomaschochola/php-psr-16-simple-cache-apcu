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
        return $this->deleteKey(CacheKeys::validate($key));
    }

    #[NoDiscard()]
    #[Override()]
    public function deleteMultiple(iterable $keys): bool
    {
        $ok = true;

        foreach ($keys as $key) {
            $ok = $this->deleteKey(CacheKeys::validate($key)) && $ok;
        }

        return $ok;
    }

    #[NoDiscard()]
    #[Override()]
    public function get(string $key, mixed $default = null): mixed
    {
        $ok = false;
        $value = apcu_fetch(CacheKeys::validate($key), $ok);

        if ($ok) {
            return $value;
        }

        return $default;
    }

    #[NoDiscard()]
    #[Override()]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $validatedKeys = [];

        foreach ($keys as $key) {
            $validatedKeys[] = CacheKeys::validate($key);
        }

        $values = apcu_fetch($validatedKeys);

        assert(is_array($values));

        foreach ($validatedKeys as $key) {
            $value = $values[$key] ?? null;

            if ($value !== null || array_key_exists($key, $values)) {
                yield $key => $value;
            } else {
                yield $key => $default;
            }
        }
    }

    #[NoDiscard()]
    #[Override()]
    public function has(string $key): bool
    {
        return apcu_exists(CacheKeys::validate($key));
    }

    #[NoDiscard()]
    #[Override()]
    public function set(string $key, mixed $value, DateInterval | int | null $ttl = null): bool
    {
        $seconds = $ttl instanceof DateInterval ? self::getInterval($ttl) : $ttl;

        return $this->storeKey(CacheKeys::validate($key), $value, $seconds);
    }

    /**
     * @param iterable<mixed, mixed> $values
     */
    #[NoDiscard()]
    #[Override()]
    public function setMultiple(iterable $values, DateInterval | int | null $ttl = null): bool
    {
        $ok = true;
        $seconds = $ttl instanceof DateInterval ? self::getInterval($ttl) : $ttl;

        foreach ($values as $key => $value) {
            $key = CacheKeys::validate(is_int($key) ? (string) $key : $key);
            $ok = $this->storeKey($key, $value, $seconds) && $ok;
        }

        return $ok;
    }

    #[NoDiscard()]
    private static function getInterval(DateInterval $interval): int
    {
        $now = new DateTimeImmutable();

        return $now->add($interval)->getTimestamp() - $now->getTimestamp();
    }

    #[NoDiscard()]
    private function deleteKey(string $key): bool
    {
        return apcu_delete($key) || !apcu_exists($key);
    }

    #[NoDiscard()]
    private function storeKey(string $key, mixed $value, ?int $seconds): bool
    {
        if ($seconds !== null && $seconds <= 0) {
            return $this->deleteKey($key);
        }

        return apcu_store($key, $value, $seconds ?? 0);
    }
}
