<?php
declare(strict_types=1);

namespace Scheduling\Test\TestCase;

use Cake\Chronos\Chronos;
use Cake\TestSuite\TestCase;
use Scheduling\CacheEventMutex;
use Scheduling\Event;

class EventTest extends TestCase
{
    private function createEvent(string $command): Event
    {
        $mutex = new CacheEventMutex();

        return new Event($mutex, $command);
    }

    public function testBuildCommandWithOutputRedirectionUnix(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Unix-specific test');
        }

        $event = $this->createEvent('php -v');
        $event->sendOutputTo('/tmp/test.log');

        $command = $event->buildCommand();

        $this->assertStringContainsString('php -v', $command);
        $this->assertStringContainsString('/tmp/test.log', $command);
    }

    public function testBuildCommandWithOutputRedirectionWindows(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->markTestSkipped('Windows-specific test');
        }

        $event = $this->createEvent('php -v');
        $event->sendOutputTo('C:\\temp\\test.log');

        $command = $event->buildCommand();

        $this->assertStringContainsString('php -v', $command);
        $this->assertStringContainsString('C:\\temp\\test.log', $command);
    }

    public function testBuildCommandWithAppendOutputUnix(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Unix-specific test');
        }

        $event = $this->createEvent('php -v');
        $event->appendOutputTo('/var/log/app.log');

        $command = $event->buildCommand();

        $this->assertStringContainsString('php -v', $command);
        $this->assertStringContainsString(">> '/var/log/app.log'", $command);
    }

    public function testBuildCommandWithAppendOutputWindows(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->markTestSkipped('Windows-specific test');
        }

        $event = $this->createEvent('php -v');
        $event->appendOutputTo('C:\\logs\\app.log');

        $command = $event->buildCommand();

        $this->assertStringContainsString('php -v', $command);
        $this->assertStringContainsString('>> "C:\\logs\\app.log"', $command);
    }

    public function testCronExpressionGeneration(): void
    {
        $event = $this->createEvent('php -v');
        $event->daily()->at('10:30');

        $this->assertSame('30 10 * * *', $event->getExpression());
    }

    public function testCronExpressionWithMultipleFrequencies(): void
    {
        $event = $this->createEvent('php -v');
        $event->hourly()->at('15');

        $this->assertSame('0 15 * * *', $event->getExpression());
    }

    public function testEventRunsAtCorrectTime(): void
    {
        $event = $this->createEvent('php -v');
        $event->daily()->at('10:30');

        // Test at 10:30 - should be due
        Chronos::setTestNow('2024-01-01 10:30:00');
        $this->assertTrue($event->isDue());

        // Test at 10:29 - should not be due
        Chronos::setTestNow('2024-01-01 10:29:00');
        $this->assertFalse($event->isDue());

        // Reset test time
        Chronos::setTestNow();
    }

    public function testMutexPreventsOverlappingExecution(): void
    {
        $mutex = $this->createMock(\Scheduling\EventMutexInterface::class);
        $mutex->method('exists')->willReturn(true);
        $mutex->method('create')->willReturn(false);

        $event = new Event($mutex, 'php -v');
        $event->withoutOverlapping();

        $this->assertTrue($event->shouldSkipDueToOverlapping());
    }

    public function testEventFiltersPreventExecution(): void
    {
        $event = $this->createEvent('php -v');
        $event->when(function () {
            return false; // Never run
        });

        $this->assertFalse($event->filtersPass());
    }

    public function testEventFiltersAllowExecution(): void
    {
        $event = $this->createEvent('php -v');
        $event->when(function () {
            return true;
        });

        $this->assertTrue($event->filtersPass());
    }

    public function testRepeatableEventBehavior(): void
    {
        $event = $this->createEvent('php -v');
        $event->repeatSeconds = 30;

        $this->assertTrue($event->isRepeatable());
        $this->assertTrue($event->shouldRepeatNow());
    }
}
