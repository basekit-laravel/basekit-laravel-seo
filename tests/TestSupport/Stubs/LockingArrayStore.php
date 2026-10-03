<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelSeo\Tests\TestSupport\Stubs;

use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;

/**
 * An in-memory store that also supports locking.
 *
 * The `array` driver has no lock support, so the sitemap generator falls
 * through to a direct rebuild there. This stub exercises the other branch: it
 * reports itself as a LockProvider and runs the callback inside `get()` exactly
 * as a real shared lock would.
 *
 * The optional `$onAcquire` hook runs *after* the lock is taken but *before* the
 * callback, which is how a test simulates another worker having just populated
 * the cache while this one was queued behind the lock.
 */
final class LockingArrayStore extends ArrayStore implements LockProvider
{
    /** @var callable(): void */
    private $onAcquire;

    public function __construct(?callable $onAcquire = null)
    {
        parent::__construct();

        $this->onAcquire = $onAcquire ?? static fn (): null => null;
    }

    public function lock($name, $seconds = 0, $owner = null): Lock
    {
        return new class($this->onAcquire) implements Lock
        {
            public function __construct(private $onAcquire) {}

            public function get($callback = null)
            {
                ($this->onAcquire)();

                return $callback === null ? true : $callback();
            }

            public function block($seconds, $callback = null)
            {
                return $this->get($callback);
            }

            public function release(): void {}

            public function forceRelease(): void {}

            public function getCurrentOwner()
            {
                return null;
            }

            public function owner()
            {
                return null;
            }
        };
    }
}
