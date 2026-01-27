<?php
declare(strict_types=1);

namespace Scheduling\Widget;

use Exception;
use Rhythm\Widget\BaseWidget;

/**
 * Scheduled Tasks Widget
 *
 * Displays monitored scheduled tasks status from Rhythm data.
 */
class ScheduledTasksWidget extends BaseWidget
{
    /**
     * Get widget data
     *
     * @param array<string, mixed> $options Widget options
     * @return array<string, mixed>
     */
    public function getData(array $options = []): array
    {
        return $this->remember(function () {
            try {
                $summaryValues = $this->rhythm->getStorage()->values('scheduled_tasks', ['summary']);

                $summary = [];

                if ($summaryValues->count() > 0) {
                    $summaryData = $summaryValues->first();
                    if ($summaryData && $summaryData->value) {
                        $summary = json_decode($summaryData->value, true) ?: [];
                    }
                }

                $detailsValues = $this->rhythm->getStorage()->values('scheduled_tasks', ['details']);

                $taskDetails = [];
                if ($detailsValues->count() > 0) {
                    $detailsData = $detailsValues->first();
                    if ($detailsData && $detailsData->value) {
                        $taskDetails = json_decode($detailsData->value, true) ?: [];
                    }
                }

                return [
                    'summary' => $summary,
                    'tasks' => $taskDetails,
                    'total_tasks' => $summary['total'] ?? 0,
                    'running_tasks' => $summary['running'] ?? 0,
                    'failed_tasks' => $summary['failed'] ?? 0,
                    'completed_tasks' => $summary['completed'] ?? 0,
                    'overdue_tasks' => $summary['overdue'] ?? 0,
                    'last_updated' => $summary['timestamp'] ?? null,
                ];
            } catch (Exception $e) {
                return [
                    'summary' => [],
                    'tasks' => [],
                    'total_tasks' => 0,
                    'running_tasks' => 0,
                    'failed_tasks' => 0,
                    'completed_tasks' => 0,
                    'overdue_tasks' => 0,
                    'last_updated' => null,
                    'error' => $e->getMessage(),
                ];
            }
        }, 'scheduled_tasks_widget', $this->getRefreshInterval());
    }

    /**
     * Get recorder name
     *
     * @return string
     */
    protected function getRecorderName(): string
    {
        return 'scheduled_tasks';
    }

    /**
     * Get template name
     *
     * @return string
     */
    public function getTemplate(): string
    {
        return 'Scheduling.widgets/scheduled_tasks';
    }

    /**
     * Get refresh interval in seconds
     *
     * @return int
     */
    public function getRefreshInterval(): int
    {
        return $this->getConfigValue('refreshInterval', 30);
    }

    /**
     * Get default icon for this widget
     *
     * @return string|null
     */
    protected function getDefaultIcon(): ?string
    {
        return 'fas fa-clock';
    }
}
