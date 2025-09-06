<?php
declare(strict_types=1);

namespace Scheduling;

/**
 * Pending Event Attributes
 *
 * Manages pending attributes for scheduled events.
 */
class PendingEventAttributes
{
    use ManageAttributesTrait;
    use ManageFrequenciesTrait;

    /**
     * The output location for the command.
     *
     * @var string|null
     */
    public ?string $output = null;

    /**
     * Whether to append to the output file.
     *
     * @var bool
     */
    public bool $shouldAppendOutput = false;

    /**
     * Create a new pending event attributes instance.
     *
     * @param \Scheduling\Schedule $schedule The schedule instance
     */
    public function __construct(
        protected Schedule $schedule,
    ) {
    }

    /**
     * Do not allow the event to overlap each other.
     * The expiration time of the underlying cache lock may be specified in minutes.
     *
     * @param int $expiresAt The expiration time in minutes
     * @return $this
     */
    public function withoutOverlapping(int $expiresAt = 1440)
    {
        $this->withoutOverlapping = true;
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * Merge the current attributes into the given event.
     *
     * @param \Scheduling\Event $event The event
     * @return void
     */
    public function mergeAttributes(Event $event): void
    {
        $event->expression = $this->expression;
        $event->repeatSeconds = $this->repeatSeconds;

        if ($this->description !== null) {
            $event->name($this->description);
        }

        if ($this->timezone !== null) {
            $event->timezone($this->timezone);
        }

        if ($this->user !== null) {
            $event->user = $this->user;
        }

        if ($this->evenInMaintenanceMode) {
            $event->evenInMaintenanceMode();
        }

        if ($this->withoutOverlapping) {
            $event->withoutOverlapping($this->expiresAt);
        }

        if ($this->onOneServer) {
            $event->onOneServer();
        }

        if ($this->runInBackground) {
            $event->runInBackground();
        }

        foreach ($this->filters as $filter) {
            $event->when($filter);
        }

        foreach ($this->rejects as $reject) {
            $event->skip($reject);
        }
    }

    /**
     * Proxy missing methods onto the underlying schedule.
     *
     * @param string $method The method name
     * @param array<mixed> $parameters The parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->schedule->{$method}(...$parameters);
    }
}
