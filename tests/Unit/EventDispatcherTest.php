<?php

declare(strict_types=1);

namespace JardisAdapter\EventDispatcher\Tests\Unit;

use JardisAdapter\EventDispatcher\Event;
use JardisAdapter\EventDispatcher\EventDispatcher;
use JardisAdapter\EventDispatcher\ListenerProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

final class EventDispatcherTest extends TestCase
{
    public function testDispatchReturnsEvent(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $event = new stdClass();
        $result = $dispatcher->dispatch($event);

        $this->assertSame($event, $result);
    }

    public function testDispatchCallsListener(): void
    {
        $called = false;

        $provider = new ListenerProvider();
        $provider->listen(stdClass::class, static function () use (&$called): void {
            $called = true;
        });

        $dispatcher = new EventDispatcher($provider);
        $dispatcher->dispatch(new stdClass());

        $this->assertTrue($called);
    }

    public function testDispatchCallsListenersInPriorityOrder(): void
    {
        $order = [];

        $provider = new ListenerProvider();
        $provider->listen(stdClass::class, static function () use (&$order): void {
            $order[] = 'first';
        }, priority: 10);
        $provider->listen(stdClass::class, static function () use (&$order): void {
            $order[] = 'second';
        }, priority: 0);

        $dispatcher = new EventDispatcher($provider);
        $dispatcher->dispatch(new stdClass());

        $this->assertSame(['first', 'second'], $order);
    }

    public function testDispatchStopsOnStoppableEvent(): void
    {
        $secondCalled = false;

        $provider = new ListenerProvider();
        $provider->listen(TestStoppableEvent::class, static function (TestStoppableEvent $event): void {
            $event->stopPropagation();
        }, priority: 10);
        $provider->listen(TestStoppableEvent::class, static function () use (&$secondCalled): void {
            $secondCalled = true;
        }, priority: 0);

        $dispatcher = new EventDispatcher($provider);
        $dispatcher->dispatch(new TestStoppableEvent());

        $this->assertFalse($secondCalled);
    }

    public function testDispatchSkipsAlreadyStoppedEvent(): void
    {
        $called = false;

        $provider = new ListenerProvider();
        $provider->listen(TestStoppableEvent::class, static function () use (&$called): void {
            $called = true;
        });

        $event = new TestStoppableEvent();
        $event->stopPropagation();

        $dispatcher = new EventDispatcher($provider);
        $dispatcher->dispatch($event);

        $this->assertFalse($called);
    }

    public function testDispatchWithNoListeners(): void
    {
        $provider = new ListenerProvider();
        $dispatcher = new EventDispatcher($provider);

        $event = new stdClass();
        $result = $dispatcher->dispatch($event);

        $this->assertSame($event, $result);
    }

    public function testExceptionPropagatesFromListener(): void
    {
        $provider = new ListenerProvider();
        $provider->listen(stdClass::class, static function (): void {
            throw new RuntimeException('Listener failed');
        });

        $dispatcher = new EventDispatcher($provider);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Listener failed');

        $dispatcher->dispatch(new stdClass());
    }

    public function testDispatchPassesEventToListener(): void
    {
        $captured = null;

        $provider = new ListenerProvider();
        $provider->listen(stdClass::class, static function (stdClass $event) use (&$captured): void {
            $captured = $event;
        });

        $event = new stdClass();
        $event->value = 'test';

        $dispatcher = new EventDispatcher($provider);
        $dispatcher->dispatch($event);

        $this->assertSame($event, $captured);
    }

    public function testListenerCanModifyEvent(): void
    {
        $provider = new ListenerProvider();
        $provider->listen(stdClass::class, static function (stdClass $event): void {
            $event->modified = true;
        });

        $event = new stdClass();

        $dispatcher = new EventDispatcher($provider);
        $result = $dispatcher->dispatch($event);

        $this->assertTrue($result->modified);
    }

    public function testMultipleListenersCalledSequentially(): void
    {
        $provider = new ListenerProvider();
        $provider->listen(stdClass::class, static function (stdClass $event): void {
            $event->value = ($event->value ?? 0) + 1;
        });
        $provider->listen(stdClass::class, static function (stdClass $event): void {
            $event->value = ($event->value ?? 0) + 10;
        });

        $event = new stdClass();

        $dispatcher = new EventDispatcher($provider);
        $dispatcher->dispatch($event);

        $this->assertSame(11, $event->value);
    }
}

// --- Test double ---

final class TestStoppableEvent extends Event
{
}
