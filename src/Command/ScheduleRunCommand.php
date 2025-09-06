<?php
declare(strict_types=1);

namespace Scheduling\Command;

use Cake\Chronos\Chronos;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Scheduling\Event\ScheduledTaskFailed;
use Scheduling\Event\ScheduledTaskFinished;
use Scheduling\Event\ScheduledTaskSkipped;
use Scheduling\Event\ScheduledTaskStarting;
use Scheduling\Service\ScheduleMonitorService;

/**
 * Schedule Run Command
 *
 * Main command executed by cron to run due scheduled events.
 */
class ScheduleRunCommand extends BaseSchedulerCommand
{
    /**
     * Get the description.
     *
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Run the scheduled events';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser->setDescription(self::getDescription());

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $schedule = $this->getSchedule();
        $dueEvents = $schedule->dueEvents();

        if (empty($dueEvents)) {
            if ($args->getOption('verbose')) {
                $io->info('No scheduled events are due to run.');
            }

            return static::CODE_SUCCESS;
        }

        $io->info(sprintf('Running %d scheduled event(s)...', count($dueEvents)));

        $startedAt = microtime(true);
        $hasError = false;

        try {
            $service = new ScheduleMonitorService($schedule);
            $service->syncMonitoredTasks();
        } catch (\Exception $e) {
        }

        foreach ($dueEvents as $event) {
            if ($event->isRepeatable()) {
                continue;
            }

            if (!$event->filtersPass()) {
                $this->dispatchSchedulerEvent(new ScheduledTaskSkipped($event));
                continue;
            }

            if ($event->onOneServer && !$schedule->serverShouldRun($event, Chronos::now())) {
                if ($args->getOption('verbose')) {
                    $io->info(sprintf('Skipping [%s] - already running on another server.', $event->getSummaryForDisplay()));
                }
                continue;
            }

            if (!$this->runEvent($event, $io, (bool)$args->getOption('verbose'))) {
                $hasError = true;
            }
        }

        $repeatableEvents = array_filter($dueEvents, function ($event) {
            return $event->isRepeatable();
        });

        if ($args->getOption('verbose')) {
            $io->info(sprintf('Found %d repeatable events out of %d total events', count($repeatableEvents), count($dueEvents)));
        }

        if (!empty($repeatableEvents)) {
            $this->repeatEvents($repeatableEvents, $io, (bool)$args->getOption('verbose'));
        }

        $runtime = round((microtime(true) - $startedAt) * 1000.0, 2);
        $io->success(sprintf('Scheduled events completed in %sms.', $runtime));

        return $hasError ? static::CODE_ERROR : static::CODE_SUCCESS;
    }

    /**
     * Run a single scheduled event.
     *
     * @param \Scheduling\Event $event The event to run
     * @param \Cake\Console\ConsoleIo $io The console io
     * @param bool $verbose Whether to show verbose output
     * @return bool True if successful, false if failed
     */
    protected function runEvent($event, ConsoleIo $io, bool $verbose): bool
    {
        $summary = $event->getSummaryForDisplay();

        if ($verbose) {
            $io->info(sprintf('Running scheduled command: %s', $summary));
        }

        $this->dispatchSchedulerEvent(new ScheduledTaskStarting($this, $event));

        $startedAt = microtime(true);

        try {
            $event->run();
            $runtime = round((microtime(true) - $startedAt) * 1000.0, 2);

            $this->dispatchSchedulerEvent(new ScheduledTaskFinished($this, $event, $runtime));

            if ($verbose) {
                $io->success(sprintf('Successfully ran scheduled command: %s (%sms)', $summary, $runtime));
            }

            return true;
        } catch (\Throwable $e) {
            $this->dispatchSchedulerEvent(new ScheduledTaskFailed($this, $event, $e));

            $io->error(sprintf('Failed to run scheduled command: %s - %s', $summary, $e->getMessage()));

            return false;
        }
    }

    /**
     * Run the given repeating events in a tight loop.
     *
     * @param array<\Scheduling\Event> $events The repeatable events
     * @param \Cake\Console\ConsoleIo $io The console io
     * @param bool $verbose Whether to show verbose output
     * @return void
     */
    protected function repeatEvents(array $events, ConsoleIo $io, bool $verbose): void
    {
        $startedAt = \Cake\Chronos\Chronos::now();
        $endOfMinute = $startedAt->modify('59 seconds');

        if ($verbose) {
            $io->info(sprintf(
                'Running %d repeatable event(s) until %s...',
                count($events),
                $endOfMinute->format('H:i:s')
            ));
        }

        if ($verbose) {
            foreach ($events as $event) {
                $io->info(sprintf('  - %s (repeat every %ds)', $event->getSummaryForDisplay(), $event->repeatSeconds));
            }
        }

        while (\Cake\Chronos\Chronos::now()->lessThanOrEquals($endOfMinute)) {
            foreach ($events as $event) {
                if (!$event->shouldRepeatNow()) {
                    continue;
                }

                if ($event->shouldSkipDueToOverlapping()) {
                    if ($verbose) {
                        $io->info(sprintf('Skipping repeatable [%s] - overlapping execution.', $event->getSummaryForDisplay()));
                    }
                    continue;
                }

                if (!$event->filtersPass()) {
                    if ($verbose) {
                        $io->info(sprintf('Skipping repeatable [%s] - other filters did not pass.', $event->getSummaryForDisplay()));
                    }
                    continue;
                }

                if ($event->onOneServer) {
                    $schedule = $this->getSchedule();
                    if (!$schedule->serverShouldRun($event, Chronos::now())) {
                        if ($verbose) {
                            $io->info(sprintf('Skipping repeatable [%s] - already running on another server.', $event->getSummaryForDisplay()));
                        }
                        continue;
                    }
                }

                $this->runEvent($event, $io, $verbose);
            }

            usleep(100 * 1000);
        }

        if ($verbose) {
            $io->info('Finished running repeatable events for this minute.');
        }
    }
}
