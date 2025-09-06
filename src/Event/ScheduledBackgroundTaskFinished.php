<?php
declare(strict_types=1);

namespace Scheduling\Event;

use Cake\Event\Event;
use Scheduling\Command\BaseSchedulerCommand;
use Scheduling\Event as ScheduledEvent;

/**
 * Scheduled Background Task Finished Event
 *
 * Dispatched when a background scheduled task has finished.
 *
 * @extends \Cake\Event\Event<\Scheduling\Command\BaseSchedulerCommand>
 */
class ScheduledBackgroundTaskFinished extends Event
{
    /**
     * Create a new event instance.
     *
     * @param \Scheduling\Command\BaseSchedulerCommand $subject The command that triggered this event
     * @param \Scheduling\Event $event The scheduled event
     */
    public function __construct(BaseSchedulerCommand $subject, ScheduledEvent $event)
    {
        parent::__construct('Scheduling.ScheduledBackgroundTaskFinished', $subject, [
            'event' => $event,
        ]);
    }
}
