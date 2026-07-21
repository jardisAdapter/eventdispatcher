<?php

declare(strict_types=1);

namespace JardisAdapter\EventDispatcher\Tests\Unit;

use JardisAdapter\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\StoppableEventInterface;

final class EventTest extends TestCase
{
    public function testImplementsStoppableEventInterface(): void
    {
        $event = new class extends Event {};

        $this->assertInstanceOf(StoppableEventInterface::class, $event);
    }

    public function testPropagationNotStoppedByDefault(): void
    {
        $event = new class extends Event {};

        $this->assertFalse($event->isPropagationStopped());
    }

    public function testStopPropagation(): void
    {
        $event = new class extends Event {};

        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }

    public function testStopPropagationIsIdempotent(): void
    {
        $event = new class extends Event {};

        $event->stopPropagation();
        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }
}
