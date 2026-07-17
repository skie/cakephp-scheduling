<?php
declare(strict_types=1);

namespace Scheduling;

/**
 * Scheduling Monitor
 *
 * Static utility class for scheduling monitoring functionality.
 */
class SchedulingMonitor
{
    /**
     * Event type constants
     */
    public const EVENT_TYPE_CALLBACK = 'callback';
    public const EVENT_TYPE_CAKE_COMMAND = 'cake_command';
    public const EVENT_TYPE_EXEC = 'exec';
    public const EVENT_TYPE_UNKNOWN = 'unknown-event';

    /**
     * Get the CakePHP command prefix for the current OS.
     *
     * @return string The command prefix
     */
    public static function getCakeCommandPrefix(): string
    {
        return CommandBuilder::getCakeCommandPrefix();
    }

    /**
     * Generate a name from command string.
     *
     * @param string $command The command string
     * @return string
     */
    public static function generateNameFromCommand(string $command): string
    {
        $cakeCommand = self::getCakeCommandPrefix();
        if (strpos($command, $cakeCommand) === 0) {
            $commandName = substr($command, strlen($cakeCommand));

            return 'cake-' . str_replace(' ', '-', $commandName);
        }

        $parts = explode(' ', $command);
        $baseName = basename($parts[0]);

        return 'exec-' . $baseName;
    }

    /**
     * Check if a command is a CakePHP command.
     *
     * @param string $command The command string
     * @return bool True if it's a CakePHP command
     */
    public static function isCakeCommand(string $command): bool
    {
        $cakeCommand = CommandBuilder::getCakeCommandPrefix();

        return strpos($command, $cakeCommand) === 0;
    }

    /**
     * Get the event type based on event and command.
     *
     * @param mixed $event The schedule event
     * @param string|null $command The command string
     * @return string
     */
    public static function getEventType($event, ?string $command): string
    {
        if ($event instanceof \Scheduling\CallbackEvent) {
            return self::EVENT_TYPE_CALLBACK;
        }

        if (!empty($command) && self::isCakeCommand($command)) {
            return self::EVENT_TYPE_CAKE_COMMAND;
        }

        return self::EVENT_TYPE_EXEC;
    }

    /**
     * Generate a name for the event.
     *
     * @param \Scheduling\Event $event The schedule event
     * @param string|null $command The command string
     * @return string
     */
    public static function generateEventName($event, ?string $command): string
    {
        if (!empty($event->getMonitorName())) {
            return $event->getMonitorName();
        }

        if (!empty($event->getDescription())) {
            return $event->getDescription();
        }

        if (empty($command)) {
            if ($event instanceof \Scheduling\CallbackEvent) {
                return self::EVENT_TYPE_CALLBACK . '-' . uniqid();
            }

            return self::EVENT_TYPE_UNKNOWN;
        }

        return self::generateNameFromCommand($command);
    }
}
