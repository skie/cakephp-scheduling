<?php
declare(strict_types=1);

namespace Scheduling\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Schedule List Command
 *
 * Lists all scheduled events with their expressions, next run dates, and mutex status.
 */
class ScheduleListCommand extends BaseSchedulerCommand
{
    /**
     * Get the description.
     *
     * @return string
     */
    public static function getDescription(): string
    {
        return 'List all scheduled events';
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
        $events = $schedule->events();

        if (empty($events)) {
            $io->info('No scheduled events are defined.');

            return static::CODE_SUCCESS;
        }

        $io->info(sprintf('Found %d scheduled event(s):', count($events)));
        $io->out('');

        $verbose = (bool)$args->getOption('verbose');

        foreach ($events as $index => $event) {
            $this->displayEvent($event, (int)$index + 1, $io, $verbose);
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Display information about a single event.
     *
     * @param \Scheduling\Event $event The event
     * @param int $index The event index
     * @param \Cake\Console\ConsoleIo $io The console io
     * @param bool $verbose Whether to show verbose information
     * @return void
     */
    protected function displayEvent($event, int $index, ConsoleIo $io, bool $verbose): void
    {
        $summary = $event->getSummaryForDisplay();
        $expression = $event->getExpression();
        $repeatExpression = $this->getRepeatExpression($event);
        $nextRun = $event->nextRunDate();

        $io->out(sprintf('<info>%d.</info> %s', $index, $summary));
        $io->out(sprintf('    Expression: %s%s', $expression, $repeatExpression));
        $io->out(sprintf('    Next Run: %s', $nextRun->format('Y-m-d H:i:s T')));

        if ($verbose) {
            $this->displayVerboseInfo($event, $io);
        }

        $io->out('');
    }

    /**
     * Display verbose information about an event.
     *
     * @param \Scheduling\Event $event The event
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    protected function displayVerboseInfo($event, ConsoleIo $io): void
    {
        if ($event->withoutOverlapping) {
            $mutexExists = $event->mutex->exists($event);
            $status = $mutexExists ? '<error>LOCKED</error>' : '<success>FREE</success>';
            $io->out(sprintf('    Mutex: %s (expires in %d minutes)', $status, $event->expiresAt));
        }

        if ($event->onOneServer) {
            $io->out('    Server: Single server only');
        }

        if ($event->timezone) {
            $timezone = is_string($event->timezone) ? $event->timezone : $event->timezone->getName();
            $io->out(sprintf('    Timezone: %s', $timezone));
        }

        if ($event->user) {
            $io->out(sprintf('    User: %s', $event->user));
        }

        if ($event->evenInMaintenanceMode) {
            $io->out('    Maintenance: Runs even in maintenance mode');
        }

        if ($event->runInBackground) {
            $io->out('    Execution: Background');
        }

        if ($event->repeatSeconds) {
            $io->out(sprintf('    Repeat: Every %d seconds', $event->repeatSeconds));
        }

        if ($event->output !== $event->getDefaultOutput()) {
            $io->out(sprintf('    Output: %s', $event->output));
        }
    }

    /**
     * Get the repeat expression for an event.
     *
     * @param \Scheduling\Event $event The event
     * @return string The repeat expression
     */
    protected function getRepeatExpression($event): string
    {
        return $event->isRepeatable() ? " (every {$event->repeatSeconds}s)" : '';
    }
}
