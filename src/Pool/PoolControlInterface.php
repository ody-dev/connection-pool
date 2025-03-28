<?php

declare(strict_types=1);

namespace Ody\ConnectionPool\Pool;

use SplObjectStorage;

/**
 * @template TItem of object
 */
interface PoolControlInterface
{
    /**
     * @return non-empty-string
     */
    public function getName(): string;

    public function getConfig(): PoolConfig;

    public function getIdleCount(): int;

    public function getCurrentSize(): int;

    /**
     * @throws \Throwable
     */
    public function increaseItems(): bool;

    public function decreaseItems(): bool;

    /**
     * @param TItem|null $poolItemRef
     */
    public function removeItem(mixed &$poolItemRef): void;

    /**
     * @return SplObjectStorage<PoolItemWrapperInterface<TItem>, float>
     */
    public function getIdledItemStorage(): SplObjectStorage;

    /**
     * @return SplObjectStorage<TItem, PoolItemWrapperInterface<TItem>>
     */
    public function getBorrowedItemStorage(): SplObjectStorage;
}
