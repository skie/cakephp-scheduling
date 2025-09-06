<?php
declare(strict_types=1);

namespace Scheduling\Test\TestCase;

use Cake\Chronos\Chronos;
use Cake\TestSuite\TestCase;
use Scheduling\Event;
use Scheduling\Schedule;

class ScheduleTest extends TestCase
{
    private function createSchedule(): Schedule
    {
        return new Schedule();
    }

    public function testScheduleAddsEventsCorrectly(): void
    {
        $schedule = $this->createSchedule();
        $schedule->command('php -v')->daily();
        $schedule->command('php -m')->hourly();

        $events = $schedule->events();

        $this->assertCount(2, $events);
        $this->assertStringContainsString('php -v', $events[0]->getCommand());
        $this->assertStringContainsString('php -m', $events[1]->getCommand());
    }

    public function testScheduleFiltersDueEvents(): void
    {
        $schedule = $this->createSchedule();
        $schedule->command('php -v')->daily()->at('10:30');
        $schedule->command('php -m')->hourly()->at('15');

        // Mock current time as 10:30
        Chronos::setTestNow('2024-01-01 10:30:00');
        $dueEvents = $schedule->dueEvents();

        $this->assertCount(1, $dueEvents);
        $this->assertStringContainsString('php -v', $dueEvents[0]->getCommand());

        // Reset test time
        Chronos::setTestNow();
    }

    public function testScheduleHandlesRepeatableEvents(): void
    {
        $schedule = $this->createSchedule();
        $event1 = $schedule->command('php -v')->daily();
        $event1->repeatSeconds = 10;
        $event2 = $schedule->command('php -m')->daily();

        $events = $schedule->events();

        $this->assertCount(2, $events);
        $this->assertTrue($events[0]->isRepeatable());
        $this->assertFalse($events[1]->isRepeatable());
    }

    public function testScheduleHandlesOverlappingEvents(): void
    {
        $schedule = $this->createSchedule();
        $event = $schedule->command('php -v')->daily();
        $event->withoutOverlapping();

        // Test that serverShouldRun method exists and can be called
        $shouldRun = $schedule->serverShouldRun($event, Chronos::now());

        $this->assertIsBool($shouldRun);
    }

    public function testScheduleHandlesCallbackEvents(): void
    {
        $schedule = $this->createSchedule();
        $callback = function () {
            return 'test result';
        };

        $event = $schedule->call($callback);

        $this->assertInstanceOf(Event::class, $event);
        $this->assertInstanceOf(\Scheduling\CallbackEvent::class, $event);
    }
}
