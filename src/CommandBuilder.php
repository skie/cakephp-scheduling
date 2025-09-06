<?php
declare(strict_types=1);

namespace Scheduling;

/**
 * Command Builder
 *
 * Builds executable command strings for scheduled events.
 */
class CommandBuilder
{
    /**
     * Build the command for the given event.
     *
     * @param \Scheduling\Event $event The event
     * @return string The built command
     */
    public function buildCommand(Event $event): string
    {
        if ($event->runInBackground) {
            return $this->buildBackgroundCommand($event);
        }

        return $this->buildForegroundCommand($event);
    }

    /**
     * Build the command for running the event in the foreground.
     *
     * @param \Scheduling\Event $event The event
     * @return string The built command
     */
    protected function buildForegroundCommand(Event $event): string
    {
        $command = $this->normalizeCommandForOS($event->getCommand());

        if ($event->output !== $event->getDefaultOutput()) {
            $output = $this->escapeArgument($event->output);
            $command .= ($event->shouldAppendOutput ? ' >> ' : ' > ') . $output . ' 2>&1';
        }

        return $this->ensureCorrectUser($event, $command);
    }

    /**
     * Build the command for running the event in the background.
     *
     * @param \Scheduling\Event $event The event
     * @return string The built command
     */
    protected function buildBackgroundCommand(Event $event): string
    {
        $command = $this->normalizeCommandForOS($event->getCommand());
        $output = $this->escapeArgument($event->output);
        $redirect = $event->shouldAppendOutput ? ' >> ' : ' > ';

        $finished = $this->normalizeCommandForOS('php bin/cake.php schedule finish "' . $event->mutexName() . '"');

        if ($this->isWindows()) {
            return 'start /b cmd /v:on /c "(' . $command . ' & ' . $finished . ' ^!ERRORLEVEL^!)' . $redirect . $output . ' 2>&1"';
        }

        return $this->ensureCorrectUser(
            $event,
            '(' . $command . $redirect . $output . ' 2>&1 ; ' . $finished . ' "$?") > '
            . $this->escapeArgument($event->getDefaultOutput()) . ' 2>&1 &'
        );
    }

    /**
     * Finalize the event's command syntax with the correct user.
     *
     * @param \Scheduling\Event $event The event
     * @param string $command The command
     * @return string The final command
     */
    protected function ensureCorrectUser(Event $event, string $command): string
    {
        return $event->user && !$this->isWindows() ? 'sudo -u ' . $event->user . ' -- sh -c \'' . $command . '\'' : $command;
    }

    /**
     * Escape an argument for shell execution.
     *
     * @param string $argument The argument to escape
     * @return string The escaped argument
     */
    protected function escapeArgument(string $argument): string
    {
        if ($this->isWindows()) {
            return '"' . str_replace('"', '""', $argument) . '"';
        }

        return "'" . str_replace("'", "'\"'\"'", $argument) . "'";
    }

    /**
     * Check if the current OS is Windows.
     *
     * @return bool True if Windows
     */
    protected function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * Normalize command for the current operating system.
     *
     * @param string $command The command to normalize
     * @return string The normalized command
     */
    protected function normalizeCommandForOS(string $command): string
    {
        if ($this->isWindows()) {
            $command = str_replace('/', '\\', $command);

            if (str_starts_with($command, 'bin\\cake.php')) {
                $command = 'php ' . $command;
            }
        }

        return $command;
    }
}
