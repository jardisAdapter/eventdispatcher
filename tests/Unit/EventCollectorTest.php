<?php

declare(strict_types=1);

namespace JardisAdapter\EventDispatcher\Tests\Unit;

use JardisAdapter\EventDispatcher\EventCollector;
use JardisAdapter\EventDispatcher\EventDispatcher;
use JardisAdapter\EventDispatcher\ListenerProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

final class EventCollectorTest extends TestCase
{
    public function testRecordCollectsEvents(): void
    {
        $collector = new EventCollector();
        $collector->record(new stdClass());
        $collector->record(new stdClass());

        $this->assertSame(2, $collector->count());
    }

    public function testEventsReturnsRecordedEvents(): void
    {
        $event = new stdClass();

        $collector = new EventCollector();
        $collector->record($event);

        $this->assertSame([$event], $collector->events());
    }

    public function testDispatchAllDispatchesAndClears(): void
    {
        $dispatched = [];

        $provider = new ListenerProvider();
        $provider->listen(stdClass::class, static function (stdClass $event) use (&$dispatched): void {
            $dispatched[] = $event;
        });

        $dispatcher = new EventDispatcher($provider);

        $event1 = new stdClass();
        $event2 = new stdClass();

        $collector = new EventCollector();
        $collector->record($event1)->record($event2);
        $collector->dispatchAll($dispatcher);

        $this->assertSame([$event1, $event2], $dispatched);
        $this->assertSame(0, $collector->count());
    }

    public function testDispatchAllWithNoEvents(): void
    {
        $called = false;

        $provider = new ListenerProvider();
        $provider->listen(stdClass::class, static function () use (&$called): void {
            $called = true;
        });

        $dispatcher = new EventDispatcher($provider);

        $collector = new EventCollector();
        $collector->dispatchAll($dispatcher);

        $this->assertFalse($called);
    }

    public function testClearRemovesAllEvents(): void
    {
        $collector = new EventCollector();
        $collector->record(new stdClass());
        $collector->record(new stdClass());
        $collector->clear();

        $this->assertSame(0, $collector->count());
        $this->assertSame([], $collector->events());
    }

    public function testFluentApi(): void
    {
        $collector = new EventCollector();

        $result = $collector
            ->record(new stdClass())
            ->record(new stdClass());

        $this->assertSame($collector, $result);
    }

    public function testCountReturnsZeroWhenEmpty(): void
    {
        $collector = new EventCollector();

        $this->assertSame(0, $collector->count());
    }

    public function testDispatchAllPreservesOrder(): void
    {
        $order = [];

        $provider = new ListenerProvider();
        $provider->listen(stdClass::class, static function (stdClass $event) use (&$order): void {
            $order[] = $event->label;
        });

        $dispatcher = new EventDispatcher($provider);

        $first = new stdClass();
        $first->label = 'first';
        $second = new stdClass();
        $second->label = 'second';

        $collector = new EventCollector();
        $collector->record($first)->record($second);
        $collector->dispatchAll($dispatcher);

        $this->assertSame(['first', 'second'], $order);
    }

    public function testCanRecordAfterDispatchAll(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $collector = new EventCollector();
        $collector->record(new stdClass());
        $collector->dispatchAll($dispatcher);

        $collector->record(new stdClass());

        $this->assertSame(1, $collector->count());
    }
}
