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
     * Check if the current OS is Windows.
     *
     * @return bool True if Windows
     */
    public static function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * Get the CakePHP command prefix for the current OS.
     *
     * @return string The command prefix
     */
    public static function getCakeCommandPrefix(): string
    {
        return self::isWindows() ? 'bin\cake.php ' : 'bin/cake.php ';
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
        $cakeCommand = self::getCakeCommandPrefix();

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
            return 'callback';
        }

        if (!empty($command) && self::isCakeCommand($command)) {
            return 'cake_command';
        }

        return 'exec';
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
                return 'callback-' . uniqid();
            }

            return 'unknown-event';
        }

        return self::generateNameFromCommand($command);
    }
}
