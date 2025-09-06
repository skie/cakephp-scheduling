<?php
declare(strict_types=1);

namespace Scheduling\Event;

use Cake\Event\Event;
use Scheduling\Command\BaseSchedulerCommand;
use Scheduling\Event as ScheduledEvent;

/**
 * Scheduled Task Starting Event
 *
 * Dispatched when a scheduled task is about to start.
 *
 * @extends \Cake\Event\Event<\Scheduling\Command\BaseSchedulerCommand>
 */
class ScheduledTaskStarting extends Event
{
    /**
     * Create a new event instance.
     *
     * @param \Scheduling\Command\BaseSchedulerCommand $subject The command that triggered this event
     * @param \Scheduling\Event $event The scheduled event
     */
    public function __construct(BaseSchedulerCommand $subject, ScheduledEvent $event)
    {
        parent::__construct('Scheduling.ScheduledTaskStarting', $subject, [
            'event' => $event,
        ]);
    }
}
