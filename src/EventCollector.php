<?php

declare(strict_types=1);

namespace JardisAdapter\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Collects domain events for deferred dispatching.
 */
final class EventCollector
{
    /** @var list<object> */
    private array $events = [];

    public function record(object $event): self
    {
        $this->events[] = $event;

        return $this;
    }

    public function dispatchAll(EventDispatcherInterface $dispatcher): self
    {
        $events = $this->events;
        $this->events = [];

        foreach ($events as $event) {
            $dispatcher->dispatch($event);
        }

        return $this;
    }

    /** @return list<object> */
    public function events(): array
    {
        return $this->events;
    }

    public function clear(): self
    {
        $this->events = [];

        return $this;
    }

    public function count(): int
    {
        return count($this->events);
    }
}
