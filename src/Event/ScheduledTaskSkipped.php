<?php
declare(strict_types=1);

namespace Scheduling\Event;

use Cake\Event\Event;
use Scheduling\Event as ScheduledEvent;

/**
 * Scheduled Task Skipped Event
 *
 * Dispatched when a scheduled task is skipped due to filters.
 *
 * @extends \Cake\Event\Event<\Scheduling\Event>
 */
class ScheduledTaskSkipped extends Event
{
    /**
     * Create a new event instance.
     *
     * @param \Scheduling\Event $event The scheduled event
     */
    public function __construct(ScheduledEvent $event)
    {
        parent::__construct('Scheduling.ScheduledTaskSkipped', $event, [
            'event' => $event,
        ]);
    }
}
