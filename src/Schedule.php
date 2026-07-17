<?php
declare(strict_types=1);

namespace Scheduling;

use BadMethodCallException;
use Closure;
use DateTimeInterface;
use RuntimeException;

/**
 * Schedule
 *
 * Central registry for all scheduled events.
 */
class Schedule
{
    /**
     * Day constants
     */
    public const SUNDAY = 0;
    public const MONDAY = 1;
    public const TUESDAY = 2;
    public const WEDNESDAY = 3;
    public const THURSDAY = 4;
    public const FRIDAY = 5;
    public const SATURDAY = 6;

    /**
     * All of the events on the schedule.
     *
     * @var array<\Scheduling\Event>
     */
    protected array $events = [];

    /**
     * The event mutex implementation.
     *
     * @var \Scheduling\EventMutexInterface
     */
    protected EventMutexInterface $eventMutex;

    /**
     * The scheduling mutex implementation.
     *
     * @var \Scheduling\SchedulingMutexInterface
     */
    protected SchedulingMutexInterface $schedulingMutex;

    /**
     * The timezone the date should be evaluated on.
     *
     * @var \DateTimeZone|string|null
     */
    protected $timezone;

    /**
     * The cache of mutex results.
     *
     * @var array<string, bool>
     */
    protected array $mutexCache = [];

    /**
     * The attributes to pass to the event.
     *
     * @var \Scheduling\PendingEventAttributes|null
     */
    protected ?PendingEventAttributes $attributes = null;

    /**
     * The schedule group attributes stack.
     *
     * @var array<int, \Scheduling\PendingEventAttributes>
     */
    protected array $groupStack = [];

    /**
     * Create a new schedule instance.
     *
     * @param \DateTimeZone|string|null $timezone The timezone
     * @param \Scheduling\EventMutexInterface|null $eventMutex The event mutex
     * @param \Scheduling\SchedulingMutexInterface|null $schedulingMutex The scheduling mutex
     */
    public function __construct($timezone = null, ?EventMutexInterface $eventMutex = null, ?SchedulingMutexInterface $schedulingMutex = null)
    {
        $this->timezone = $timezone;
        $this->eventMutex = $eventMutex ?? new CacheEventMutex();
        $this->schedulingMutex = $schedulingMutex ?? new CacheSchedulingMutex();
    }

    /**
     * Add a new callback event to the schedule.
     *
     * @param string|callable $callback The callback
     * @param array<mixed> $parameters The parameters
     * @return \Scheduling\CallbackEvent
     */
    public function call($callback, array $parameters = []): CallbackEvent
    {
        $this->events[] = $event = new CallbackEvent(
            $this->eventMutex,
            $callback,
            $parameters,
            $this->timezone
        );

        $this->mergePendingAttributes($event);

        return $event;
    }

    /**
     * Add a new CakePHP command event to the schedule.
     *
     * @param string $command The command
     * @param array<mixed> $parameters The parameters
     * @return \Scheduling\Event
     */
    public function command(string $command, array $parameters = []): Event
    {
        if (count($parameters)) {
            $command .= ' ' . $this->compileParameters($parameters);
        }

        $cakeCommand = CommandBuilder::getCakeCommandPrefix();

        return $this->exec($cakeCommand . $command);
    }

    /**
     * Add a new command event to the schedule.
     *
     * @param string $command The command
     * @param array<mixed> $parameters The parameters
     * @return \Scheduling\Event
     */
    public function exec(string $command, array $parameters = []): Event
    {
        if (count($parameters)) {
            $command .= ' ' . $this->compileParameters($parameters);
        }

        $this->events[] = $event = new Event($this->eventMutex, $command, $this->timezone);

        $this->mergePendingAttributes($event);

        return $event;
    }

    /**
     * Create new schedule group.
     *
     * @param \Closure $events The events closure
     * @return void
     * @throws \RuntimeException
     */
    public function group(Closure $events): void
    {
        if ($this->attributes === null) {
            throw new RuntimeException('Invoke an attribute method such as Schedule::daily() before defining a schedule group.');
        }

        $this->groupStack[] = $this->attributes;

        $events($this);

        array_pop($this->groupStack);
    }

    /**
     * Merge the current group attributes with the given event.
     *
     * @param \Scheduling\Event $event The event
     * @return void
     */
    protected function mergePendingAttributes(Event $event): void
    {
        if (isset($this->attributes)) {
            $this->attributes->mergeAttributes($event);

            $this->attributes = null;
        }

        if (!empty($this->groupStack)) {
            $group = end($this->groupStack);

            $group->mergeAttributes($event);
        }
    }

    /**
     * Compile parameters for a command.
     *
     * @param array<mixed> $parameters The parameters
     * @return string The compiled parameters
     */
    protected function compileParameters(array $parameters): string
    {
        $compiled = [];

        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                $compiled[] = $this->compileArrayInput($key, $value);
            } else {
                $valueString = (string)$value;
                if (!is_numeric($value) && !preg_match('/^(-.$|--.*)/i', $valueString)) {
                    $valueString = $this->escapeArgument($valueString);
                }

                $keyString = (string)$key;
                $compiled[] = is_numeric($key) ? $valueString : "{$keyString}={$valueString}";
            }
        }

        return implode(' ', $compiled);
    }

    /**
     * Compile array input for a command.
     *
     * @param string|int $key The key
     * @param array<mixed> $value The value
     * @return string The compiled input
     */
    public function compileArrayInput($key, array $value): string
    {
        $compiled = [];

        foreach ($value as $item) {
            $itemString = (string)$item;
            $compiled[] = $this->escapeArgument($itemString);
        }

        $keyString = (string)$key;
        if (str_starts_with($keyString, '--')) {
            $compiled = array_map(function ($item) use ($keyString) {
                return "{$keyString}={$item}";
            }, $compiled);
        } elseif (str_starts_with($keyString, '-')) {
            $compiled = array_map(function ($item) use ($keyString) {
                return "{$keyString} {$item}";
            }, $compiled);
        }

        return implode(' ', $compiled);
    }

    /**
     * Escape an argument for shell execution.
     *
     * @param string $argument The argument
     * @return string The escaped argument
     */
    protected function escapeArgument(string $argument): string
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return '"' . str_replace('"', '""', $argument) . '"';
        }

        return "'" . str_replace("'", "'\"'\"'", $argument) . "'";
    }

    /**
     * Determine if the server is allowed to run this event.
     *
     * @param \Scheduling\Event $event The event
     * @param \DateTimeInterface $time The time
     * @return bool True if server should run
     */
    public function serverShouldRun(Event $event, DateTimeInterface $time): bool
    {
        return $this->mutexCache[$event->mutexName()] ??= $this->schedulingMutex->create($event, $time);
    }

    /**
     * Get all of the events on the schedule that are due.
     *
     * @return array<\Scheduling\Event>
     */
    public function dueEvents(): array
    {
        return array_filter($this->events, function (Event $event) {
            return $event->isDue();
        });
    }

    /**
     * Get all of the events on the schedule.
     *
     * @return array<\Scheduling\Event>
     */
    public function events(): array
    {
        return $this->events;
    }

    /**
     * Specify the cache store that should be used to store mutexes.
     *
     * @param string $store The cache store
     * @return $this
     */
    public function useCache(string $store)
    {
        if ($this->eventMutex instanceof CacheAwareInterface) {
            $this->eventMutex->useStore($store);
        }

        if ($this->schedulingMutex instanceof CacheAwareInterface) {
            $this->schedulingMutex->useStore($store);
        }

        return $this;
    }

    /**
     * Dynamically handle calls into the schedule instance.
     *
     * @param string $method The method name
     * @param array<mixed> $parameters The parameters
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (method_exists(PendingEventAttributes::class, $method)) {
            $this->attributes ??= end($this->groupStack) ?: new PendingEventAttributes($this);

            return $this->attributes->$method(...$parameters);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.',
            static::class,
            $method
        ));
    }
}
