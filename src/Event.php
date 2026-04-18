<?php

declare(strict_types=1);

namespace JardisAdapter\EventDispatcher;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Optional base class for domain events with propagation control.
 */
abstract class Event implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
