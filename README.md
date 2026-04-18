# Jardis Event Dispatcher

![Build Status](https://github.com/jardisAdapter/eventdispatcher/actions/workflows/ci.yml/badge.svg)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.2-777BB4.svg)](https://www.php.net/)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%208-brightgreen.svg)](phpstan.neon)
[![PSR-12](https://img.shields.io/badge/Code%20Style-PSR--12-blue.svg)](phpcs.xml)
[![PSR-14](https://img.shields.io/badge/Events-PSR--14-brightgreen.svg)](https://www.php-fig.org/psr/psr-14/)

> Part of the **[Jardis Business Platform](https://jardis.io)** — Enterprise-grade PHP components for Domain-Driven Design

**Domain Events as first-class citizens.** A lightweight PSR-14 event dispatcher — built for DDD applications where events drive the communication between layers and contexts. No framework, no overhead, no magic. Just what you need.

---

## Why this Dispatcher?

- **Four classes, zero magic** — `EventDispatcher`, `ListenerProvider`, `Event`, `EventCollector`
- **Priority ordering** — listeners with higher priority are called first
- **Type-hierarchy matching** — a listener on an interface catches all implementing events
- **Stoppable events** — break the listener chain when an event is considered handled
- **EventCollector** — collect events in the domain layer, dispatch them all at once in the application layer
- **PSR-14 compliant** — works with any PSR-14 compatible code
- **100% test coverage** — no mocks, real execution only

---

## Installation

```bash
composer require jardisadapter/eventdispatcher
```

---

## Quick Start

### Define an Event

```php
use JardisAdapter\EventDispatcher\Event;

final class OrderCreated extends Event
{
    public function __construct(
        public readonly string $orderId,
    ) {
    }
}
```

### Register Listeners and Dispatch

```php
use JardisAdapter\EventDispatcher\EventDispatcher;
use JardisAdapter\EventDispatcher\ListenerProvider;

$provider = new ListenerProvider();
$provider->listen(OrderCreated::class, function (OrderCreated $event): void {
    echo "Order {$event->orderId} created!";
});

$dispatcher = new EventDispatcher($provider);
$dispatcher->dispatch(new OrderCreated('ORD-42'));
```

---

## Listener Registration

### Priority-based Registration

```php
$provider->listen(OrderCreated::class, $sendConfirmation, priority: 10);   // first
$provider->listen(OrderCreated::class, $updateInventory, priority: 5);     // second
$provider->listen(OrderCreated::class, $logEvent);                        // last (0)
```

Higher number = higher priority = called first.

### Remove a Listener

```php
$provider->remove(OrderCreated::class, $sendConfirmation);
```

### Type-Hierarchy Matching (Wildcard)

A listener on an interface or parent class catches **all events** that implement or extend it:

```php
interface PaymentEventInterface {}

final class PaymentReceived extends Event implements PaymentEventInterface {}
final class PaymentFailed extends Event implements PaymentEventInterface {}

// Catches both PaymentReceived AND PaymentFailed
$provider->listen(PaymentEventInterface::class, $paymentAuditor);

// Catches EVERY event that extends Event
$provider->listen(Event::class, $globalLogger);
```

Direct and wildcard listeners are sorted together by priority.

---

## Stoppable Events

A listener can stop further processing:

```php
$provider->listen(OrderCreated::class, function (OrderCreated $event): void {
    if ($event->orderId === 'BLOCKED') {
        $event->stopPropagation();  // no further listeners will be called
    }
}, priority: 100);

$provider->listen(OrderCreated::class, function (OrderCreated $event): void {
    // Only called if stopPropagation() was NOT invoked
});
```

Any event extending `Event` or implementing `StoppableEventInterface` supports this automatically.

---

## EventCollector — Deferred Dispatch

Collect events in the domain layer, dispatch them later in the application layer:

```php
use JardisAdapter\EventDispatcher\EventCollector;

$collector = new EventCollector();

// In the domain layer — record events
$collector->record(new OrderCreated($orderId));
$collector->record(new InventoryReserved($itemId));

// In the application layer — after the use case completes
$collector->dispatchAll($dispatcher);  // dispatches all, clears the list
```

The collector separates the **occurrence** of an event (domain) from its **distribution** (application). Ideal for use cases that produce multiple events.

```php
$collector->count();     // number of collected events
$collector->events();    // read events without dispatching
$collector->clear();     // clear the list without dispatching
```

---

## Error Handling

| Situation | Behavior |
|-----------|----------|
| Listener throws an exception | Propagates unchanged to the caller |
| No listener registered | Event is silently ignored |
| Event already stopped | No listener is called |

No custom exception classes. Errors come from the listeners, not from the dispatcher.

---

## Architecture

```
EventDispatcher (implements EventDispatcherInterface)
  │
  │  dispatch(object $event): object
  │  └── iterates listeners, respects StoppableEventInterface
  │
  └── ListenerProvider (implements ListenerProviderInterface, EventListenerRegistryInterface)
        │
        ├── listen()    register listener with priority
        ├── remove()    remove a listener
        └── getListenersForEvent()
              └── type-hierarchy matching + priority sorting

Event (abstract, implements StoppableEventInterface)
  └── stopPropagation() / isPropagationStopped()

EventCollector
  └── record() → dispatchAll() / events() / clear() / count()
```

The dispatcher is the **postman** — it receives the event and delivers it to all recipients. The listener provider is the **address book**. The event collector is the **mailbox** in the domain layer.

---

## DDD Layer Rules

| Layer | Responsibility |
|-------|---------------|
| **Domain** | Defines event classes. Does **not** dispatch — returns events instead |
| **Application** | Receives `EventDispatcherInterface` via injection. Dispatches after use case execution |
| **Infrastructure** | Registers listeners in the `ListenerProvider` |

---

## Jardis Foundation Integration

In a Jardis DDD project, the dispatcher is wired into the `DomainKernel` via `DomainApp::eventDispatcher()`:

```php
// Inside a BoundedContext
$dispatcher = $this->resource()->eventDispatcher();

if ($dispatcher !== null) {
    $dispatcher->dispatch(new OrderCreated($orderId));
}
```

### Three-State Semantics

| Return value | Meaning |
|-------------|---------|
| `EventDispatcher` | Dispatcher active, shared via ServiceRegistry |
| `null` | Package not installed — falls back to SharedRegistry |
| `false` | Event dispatching explicitly disabled |

---

## Development

```bash
cp .env.example .env    # Once
make install             # Install dependencies
make phpunit             # Run tests
make phpstan             # Static analysis (level 8)
make phpcs               # Coding standards (PSR-12)
```

---

## Documentation

Full documentation, guides, and API reference:

**[docs.jardis.io/en/adapter/eventdispatcher](https://docs.jardis.io/en/adapter/eventdispatcher)**

---

## License

[MIT License](LICENSE.md) — free for any use, including commercial.

<!-- BEGIN jardis/dev-skills README block — do not edit by hand -->
## KI-gestützte Entwicklung

Dieses Package liefert einen Skill für Claude Code, Cursor, Continue und Aider mit. Installation im Konsumentenprojekt:

```bash
composer require --dev jardis/dev-skills
```

Mehr Details: <https://docs.jardis.io/skills>
<!-- END jardis/dev-skills README block -->
