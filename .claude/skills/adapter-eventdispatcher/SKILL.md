---
name: adapter-eventdispatcher
description: PSR-14 dispatcher, priority ordering, type-hierarchy matching, stoppable events. Use for EventDispatcher, domain events.
user-invocable: false
zone: post-active
persona: C
prerequisites: [rules-architecture, rules-patterns]
next: []
---

# EVENTDISPATCHER_COMPONENT_SKILL
> jardisadapter/eventdispatcher | NS: `JardisAdapter\EventDispatcher` | PSR-14 | PHP 8.2+

## ARCHITECTURE
```
EventDispatcher (implements EventDispatcherInterface)
  dispatch(object $event): object
    → iterates listeners from ListenerProvider, respects StoppableEventInterface

ListenerProvider (implements ListenerProviderInterface, EventListenerRegistryInterface)
  listen(string $eventClass, callable $listener, int $priority = 0): void
  remove(string $eventClass, callable $listener): void
  getListenersForEvent(object $event): list<callable>

Event (abstract, implements StoppableEventInterface)
  stopPropagation() / isPropagationStopped()

EventCollector
  record(object $event): static   → dispatchAll() / events() / clear() / count()
```

## RULES
- Higher priority number = called first. Default priority: `0`.
- Direct and wildcard listeners sorted together by priority.
- A listener on an interface/parent class receives all implementing/extending events (type-hierarchy matching).
- `stopPropagation()` skips all subsequent listeners for that dispatch call.
- `StoppableEventInterface` works with any class — not only `Event` subclasses.
- No listener registered → event silently ignored.
- Listener throws → exception propagates unchanged to caller.
- No async dispatch — synchronous by design. For cross-process events → `jardisadapter/messaging`.
- No listener discovery, no classpath scanning, no Reflection. Explicit `listen()` only.
- No `EventSubscriberInterface` — static-based subscriber pattern violates explicit-dependencies pillar.

## API / SIGNATURES
```php
use JardisAdapter\EventDispatcher\{EventDispatcher, ListenerProvider, Event, EventCollector};

$provider = new ListenerProvider();
$provider->listen(OrderCreated::class, callable $listener, int $priority = 0): void;
$provider->remove(OrderCreated::class, callable $listener): void;

$dispatcher = new EventDispatcher($provider);
$dispatcher->dispatch(new OrderCreated($orderId));   // returns the event object

// Type-hierarchy — receives ALL events implementing the interface/extending the class
$provider->listen(PaymentEventInterface::class, $paymentAuditor);
$provider->listen(Event::class, $globalLogger);

// Stoppable
$provider->listen(OrderCreated::class, function (OrderCreated $e): void {
    $e->stopPropagation();  // subsequent listeners skipped
}, priority: 100);
```

## EVENTCOLLECTOR
| Method | Returns | Description |
|--------|---------|-------------|
| `record(object $event)` | `static` | Append event (fluent) |
| `dispatchAll(EventDispatcherInterface)` | `static` | Dispatch all + clear (fluent) |
| `events()` | `list<object>` | Read collected events |
| `clear()` | `static` | Clear without dispatching (fluent) |
| `count()` | `int` | Number of queued events |

## EVENT BASE CLASS
```php
final class OrderCreated extends Event
{
    public function __construct(public readonly string $orderId) {}
}
```
Extending `Event` is optional. Any object can be dispatched; stoppable behavior requires `StoppableEventInterface`.

## KERNEL INTEGRATION
`jardiscore/foundation` is deleted. ENV bootstrap moved to `jardiscore/kernel`'s Bootstrap-Packer (`Bootstrap\BuildDomainKernelFromEnv`), which wires the dispatcher pair via two handlers under `src/Bootstrap/Handler/`: `BuildEventListenerProviderFromEnv` builds the shared `ListenerProvider`, `BuildEventDispatcherFromProvider` wraps it into the PSR-14 dispatcher.
- Access: `$kernel->eventDispatcher(): ?EventDispatcherInterface` (`DomainKernel.php:81`).
- `$kernel->eventListenerRegistry(): ?EventListenerRegistryInterface` (`DomainKernel.php:86`) — same `ListenerProvider` instance, exposed so generated `{Agg}EventRouter` scaffolds can register listeners.
- In generated behavior classes extending `{Domain}Context`: `$this->resource()->eventDispatcher()`.
- Returns the instance or `null` — there is no third state. `DomainKernel::eventDispatcher()` is typed `?EventDispatcherInterface` (`core/kernel/src/DomainKernel.php:81`), so `false` is impossible. `null` means: adapter not installed, or no listener provider was built.

## LAYER
| Layer | Role |
|-------|------|
| Domain | Defines event classes. Does NOT dispatch. |
| Application | Receives `EventDispatcherInterface` via injection. Dispatches after use case. |
| Infrastructure | Registers listeners in `ListenerProvider`. |

## DEPENDENCIES
- `psr/event-dispatcher ^1.0` — no further dependencies, no Reflection, no container.
