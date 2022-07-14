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

namespace Sassnowski\Venture\Actions;

use Sassnowski\Venture\WorkflowStepInterface;
use Throwable;

interface HandlesFailedJobs
{
    public function __invoke(
        WorkflowStepInterface $step,
        Throwable $exception,
    ): void;
}