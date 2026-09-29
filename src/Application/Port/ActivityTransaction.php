<?php

declare(strict_types=1);

namespace Youmad\Endurance\Activity\Application\Port;

interface ActivityTransaction
{
    /**
     * @template TResult
     *
     * @param \Closure(): TResult $operation
     *
     * @return TResult
     */
    public function run(\Closure $operation): mixed;
}
