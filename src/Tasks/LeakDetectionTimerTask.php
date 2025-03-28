<?php

declare(strict_types=1);

namespace Ody\ConnectionPool\Tasks;

use Ody\ConnectionPool\Pool\PoolControlInterface;
use Ody\ConnectionPool\Pool\PoolItemState;
use Ody\ConnectionPool\Pool\PoolItemWrapperInterface;
use Ody\ConnectionPool\Pool\TimerTask\TimerTaskInterface;
use Psr\Log\LoggerInterface;
use function is_null;

/**
 * @template TItem of object
 * @implements TimerTaskInterface<PoolControlInterface<TItem>>
 */
readonly class LeakDetectionTimerTask implements TimerTaskInterface
{
    public function __construct(
        public float $intervalSec,
        public float $leakDetectionThresholdSec,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function run(int $timerId, mixed $runnerRef): void
    {
        /** @var PoolControlInterface<TItem>|null $runner */
        $runner = $runnerRef->get();

        if (is_null($runner)) {
            return;
        }

        $borrowedItemStorage = $runner->getBorrowedItemStorage();

        foreach ($borrowedItemStorage as $item) {
            /** @var PoolItemWrapperInterface<TItem> $poolItemWrapper */
            $poolItemWrapper = $borrowedItemStorage[$item];

            $state = $poolItemWrapper->getState();
            $currentStateDurationSec = $poolItemWrapper->stats()['current_state_duration_sec'];

            if ($currentStateDurationSec > $this->leakDetectionThresholdSec && $state == PoolItemState::IN_USE) {
                $context = [
                    'pool_name' => $runner->getName(),
                    'item_id' => $poolItemWrapper->getId(),
                    'item_in_use_duration_sec' => $currentStateDurationSec,
                ];

                $this->logger->warning('Connection leak detection triggered', $context);
            }
        }
    }

    public function getIntervalSec(): float
    {
        return $this->intervalSec;
    }
}
