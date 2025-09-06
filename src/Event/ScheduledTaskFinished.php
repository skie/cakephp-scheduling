<?php
declare(strict_types=1);

namespace Scheduling\Event;

use Cake\Event\Event;
use Scheduling\Command\BaseSchedulerCommand;
use Scheduling\Event as ScheduledEvent;

/**
 * Scheduled Task Finished Event
 *
 * Dispatched when a scheduled task has finished successfully.
 *
 * @extends \Cake\Event\Event<\Scheduling\Command\BaseSchedulerCommand>
 */
class ScheduledTaskFinished extends Event
{
    /**
     * Create a new event instance.
     *
     * @param \Scheduling\Command\BaseSchedulerCommand $subject The command that triggered this event
     * @param \Scheduling\Event $event The scheduled event
     * @param float $runtime The execution time in seconds
     */
    public function __construct(BaseSchedulerCommand $subject, ScheduledEvent $event, float $runtime)
    {
        parent::__construct('Scheduling.ScheduledTaskFinished', $subject, [
            'event' => $event,
            'runtime' => $runtime,
        ]);
    }
}
