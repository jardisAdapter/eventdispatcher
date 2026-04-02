<?php

declare(strict_types=1);

namespace JardisAdapter\EventDispatcher\Tests\Unit;

use JardisAdapter\EventDispatcher\Event;
use JardisAdapter\EventDispatcher\ListenerProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ListenerProviderTest extends TestCase
{
    public function testReturnsEmptyIterableForUnknownEvent(): void
    {
        $provider = new ListenerProvider();

        $listeners = iterator_to_array($provider->getListenersForEvent(new stdClass()));

        $this->assertSame([], $listeners);
    }

    public function testReturnsRegisteredListener(): void
    {
        $called = false;
        $listener = static function () use (&$called): void {
            $called = true;
        };

        $provider = new ListenerProvider();
        $provider->listen(stdClass::class, $listener);

        $listeners = iterator_to_array($provider->getListenersForEvent(new stdClass()));

        $this->assertCount(1, $listeners);
        $listeners[0](new stdClass());
        $this->assertTrue($called);
    }

    public function testReturnsListenersInPriorityOrder(): void
    {
        $order = [];

        $provider = new ListenerProvider();
        $provider->listen(stdClass::class, static function () use (&$order): void {
            $order[] = 'low';
        }, priority: 0);
        $provider->listen(stdClass::class, static function () use (&$order): void {
            $order[] = 'high';
        }, priority: 10);
        $provider->listen(stdClass::class, static function () use (&$order): void {
            $order[] = 'medium';
        }, priority: 5);

        $listeners = iterator_to_array($provider->getListenersForEvent(new stdClass()));

        foreach ($listeners as $listener) {
            $listener(new stdClass());
        }

        $this->assertSame(['high', 'medium', 'low'], $order);
    }

    public function testFluentApi(): void
    {
        $provider = new ListenerProvider();

        $result = $provider
            ->listen(stdClass::class, static function (): void {})
            ->listen(stdClass::class, static function (): void {});

        $this->assertSame($provider, $result);
    }

    public function testRemoveListener(): void
    {
        $listener = static function (): void {};

        $provider = new ListenerProvider();
        $provider->listen(stdClass::class, $listener);
        $provider->remove(stdClass::class, $listener);

        $listeners = iterator_to_array($provider->getListenersForEvent(new stdClass()));

        $this->assertSame([], $listeners);
    }

    public function testRemoveNonExistentListenerDoesNothing(): void
    {
        $provider = new ListenerProvider();
        $result = $provider->remove(stdClass::class, static function (): void {});

        $this->assertSame($provider, $result);
    }

    public function testRemoveOnlyTargetedListener(): void
    {
        $keepListener = static function (): void {};
        $removeListener = static function (): void {};

        $provider = new ListenerProvider();
        $provider->listen(stdClass::class, $keepListener);
        $provider->listen(stdClass::class, $removeListener);
        $provider->remove(stdClass::class, $removeListener);

        $listeners = iterator_to_array($provider->getListenersForEvent(new stdClass()));

        $this->assertCount(1, $listeners);
    }

    public function testWildcardMatchingViaInterface(): void
    {
        $called = false;
        $listener = static function () use (&$called): void {
            $called = true;
        };

        $provider = new ListenerProvider();
        $provider->listen(TestEventInterface::class, $listener);

        $event = new TestConcreteEvent();
        $listeners = iterator_to_array($provider->getListenersForEvent($event));

        $this->assertCount(1, $listeners);
        $listeners[0]($event);
        $this->assertTrue($called);
    }

    public function testWildcardMatchingViaParentClass(): void
    {
        $called = false;
        $listener = static function () use (&$called): void {
            $called = true;
        };

        $provider = new ListenerProvider();
        $provider->listen(Event::class, $listener);

        $event = new TestDomainEvent();
        $listeners = iterator_to_array($provider->getListenersForEvent($event));

        $this->assertCount(1, $listeners);
    }

    public function testCombinesDirectAndWildcardListeners(): void
    {
        $order = [];

        $provider = new ListenerProvider();
        $provider->listen(TestDomainEvent::class, static function () use (&$order): void {
            $order[] = 'direct';
        }, priority: 5);
        $provider->listen(Event::class, static function () use (&$order): void {
            $order[] = 'wildcard';
        }, priority: 10);

        $listeners = iterator_to_array($provider->getListenersForEvent(new TestDomainEvent()));

        foreach ($listeners as $listener) {
            $listener(new TestDomainEvent());
        }

        $this->assertSame(['wildcard', 'direct'], $order);
    }

    public function testNoMatchForUnrelatedEvent(): void
    {
        $provider = new ListenerProvider();
        $provider->listen(TestDomainEvent::class, static function (): void {});

        $listeners = iterator_to_array($provider->getListenersForEvent(new stdClass()));

        $this->assertSame([], $listeners);
    }
}

// --- Test doubles ---

interface TestEventInterface
{
}

final class TestConcreteEvent implements TestEventInterface
{
}

final class TestDomainEvent extends Event
{
}


