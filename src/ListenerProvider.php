<?php

declare(strict_types=1);

namespace JardisAdapter\EventDispatcher;

use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Priority-based listener provider with type-hierarchy matching.
 */
final class ListenerProvider implements ListenerProviderInterface
{
    /** @var array<class-string, list<array{callable, int}>> */
    private array $listeners = [];

    /**
     * @param class-string $eventClass
     */
    public function listen(string $eventClass, callable $listener, int $priority = 0): self
    {
        $this->listeners[$eventClass][] = [$listener, $priority];

        return $this;
    }

    /**
     * @param class-string $eventClass
     */
    public function remove(string $eventClass, callable $listener): self
    {
        if (!isset($this->listeners[$eventClass])) {
            return $this;
        }

        $this->listeners[$eventClass] = array_values(
            array_filter(
                $this->listeners[$eventClass],
                static fn(array $entry): bool => $entry[0] !== $listener,
            ),
        );

        if ($this->listeners[$eventClass] === []) {
            unset($this->listeners[$eventClass]);
        }

        return $this;
    }

    /** @return list<callable> */
    public function getListenersForEvent(object $event): iterable
    {
        $matched = [];

        foreach ($this->listeners as $eventClass => $listeners) {
            if ($event instanceof $eventClass) {
                foreach ($listeners as $entry) {
                    $matched[] = $entry;
                }
            }
        }

        usort($matched, static fn(array $a, array $b): int => $b[1] <=> $a[1]);

        return array_map(static fn(array $entry): callable => $entry[0], $matched);
    }
}
