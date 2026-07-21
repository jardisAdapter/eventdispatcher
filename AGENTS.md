# jardisadapter/eventdispatcher

PSR-14 Event Dispatcher with priority ordering, type-hierarchy matching, stoppable events, and deferred dispatch via `EventCollector`. Synchronous, no external dependencies except `psr/event-dispatcher`.

## Usage essentials

- **Four classes, no subdirectories:** `EventDispatcher`, `ListenerProvider`, `Event`, `EventCollector`. No container, no Reflection, no ENV variables.
- **Register listeners explicitly** — no subscriber interface, no classpath scanning: `$provider->listen(OrderCreated::class, $listener, priority: 10)`. Higher priority = called first, default `0`.
- **Type-hierarchy matching:** Listeners on an interface/parent receive all implementing events (`listen(Event::class, $globalLogger)`).
- **Extending `Event` is optional.** Events must only implement `StoppableEventInterface` when `stopPropagation()` is needed — otherwise any class works.
- **Synchronous by design.** For cross-process / async → `jardisadapter/messaging`, not listener-based.
- **DDD Layer rule:** Domain defines event classes, Application dispatches, Infrastructure registers listeners. No dispatch calls in the Domain Layer.

## Full reference

https://docs.jardis.io/en/adapter/eventdispatcher
