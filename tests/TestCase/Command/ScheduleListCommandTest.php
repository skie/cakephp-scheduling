<?php
declare(strict_types=1);

namespace Scheduling\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Scheduling\Schedule;

class ScheduleListCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setAppNamespace();
        $this->configApplication(
            'TestApp\Application',
            [PLUGIN_TESTS . 'TestApp' . DS . 'config'],
        );

        // Override the default configuration to have no scheduled events
        Configure::write('Scheduling.definitions', []);
    }

    public function testListWithNoEvents(): void
    {
        $schedule = new Schedule();
        $this->mockService(Schedule::class, function () use ($schedule) {
            return $schedule;
        });

        $this->exec('schedule list');

        $this->assertExitSuccess();
        $this->assertOutputContains('No scheduled events are defined');
    }

    public function testListWithEvents(): void
    {
        $schedule = new Schedule();
        $schedule->command('echo "daily"')->daily();
        $schedule->command('echo "hourly"')->hourly();

        $this->mockService(Schedule::class, function () use ($schedule) {
            return $schedule;
        });

        $this->exec('schedule list');

        $this->assertExitSuccess();
        $this->assertOutputContains('Found 2 scheduled event(s)');
        $this->assertOutputContains('echo "daily"');
        $this->assertOutputContains('echo "hourly"');
    }

    public function testListWithVerboseOutput(): void
    {
        $schedule = new Schedule();
        $schedule->command('echo "test"')->daily()->description('Test command');

        $this->mockService(Schedule::class, function () use ($schedule) {
            return $schedule;
        });

        $this->exec('schedule list --verbose');

        $this->assertExitSuccess();
        $this->assertOutputContains('Test command');
    }
}
