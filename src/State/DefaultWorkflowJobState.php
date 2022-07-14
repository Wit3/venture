<?php

declare(strict_types=1);

/**
 * Copyright (c) 2021 Kai Sassnowski
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/ksassnowski/venture
 */

namespace Sassnowski\Venture\State;

use RuntimeException;
use Sassnowski\Venture\Models\WorkflowJob;
use Throwable;

class DefaultWorkflowJobState implements WorkflowJobState
{
    public function __construct(private WorkflowJob $job)
    {
    }

    public function hasFinished(): bool
    {
        return null !== $this->job->finished_at;
    }

    public function markAsFinished(): void
    {
        $this->job->update([
            'finished_at' => now(),
            'exception' => null,
            'failed_at' => null,
        ]);
    }

    public function hasFailed(): bool
    {
        return !$this->hasFinished() && null !== $this->job->failed_at;
    }

    public function markAsFailed(Throwable $exception): void
    {
        $this->job->update([
            'failed_at' => now(),
            'exception' => (string) $exception,
        ]);
    }

    public function isProcessing(): bool
    {
        return !$this->hasFinished()
            && !$this->hasFailed()
            && null !== $this->job->started_at;
    }

    public function markAsProcessing(): void
    {
        $this->job->update([
            'finished_at' => null,
            'failed_at' => null,
            'exception' => null,
            'started_at' => now(),
        ]);
    }

    public function isPending(): bool
    {
        return !$this->isProcessing() && !$this->hasFailed() && !$this->hasFinished();
    }

    public function isGated(): bool
    {
        if (!$this->job->manual) {
            return false;
        }

        if ($this->hasFinished() || $this->hasFailed() || $this->isProcessing()) {
            return false;
        }

        return null !== $this->job->gated_at;
    }

    public function markAsGated(): void
    {
        if (!$this->job->manual) {
            throw new RuntimeException('Only manual jobs can be marked as gated');
        }

        $this->job->update([
            'gated_at' => now(),
        ]);
    }

    /**
     * @param array<int, string> $completedSteps
     */
    public function transition(array $completedSteps): void
    {
    }

    public function canRun(): bool
    {
        if ($this->isGated()) {
            return false;
        }

        $step = $this->job->step();

        return \count(
            \array_diff(
                $step->getDependencies(),
                $this->job->workflow->finished_jobs,
            ),
        ) === 0;
    }
}