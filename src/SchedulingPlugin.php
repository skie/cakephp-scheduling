<?php
declare(strict_types=1);

namespace Scheduling;

use Cake\Console\CommandCollection;
use Cake\Console\CommandFactoryInterface;
use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventManager;
use Scheduling\Command\ScheduleClearCacheCommand;
use Scheduling\Command\ScheduleFinishCommand;
use Scheduling\Command\ScheduleListCommand;
use Scheduling\Command\ScheduleMonitorListCommand;
use Scheduling\Command\ScheduleMonitorPruneCommand;
use Scheduling\Command\ScheduleMonitorSyncCommand;
use Scheduling\Command\ScheduleRunCommand;
use Scheduling\Command\ScheduleTestCommand;
use Scheduling\Command\ScheduleWorkCommand;
use Scheduling\Listener\ScheduleMonitorListener;

/**
 * Scheduling Plugin
 *
 * CakePHP Scheduler plugin.
 * Provides simplified cron management with second-based scheduling capabilities.
 */
class SchedulingPlugin extends BasePlugin
{
    protected ?string $name = 'Scheduling';

    /**
     * Load all plugin components and bootstrap
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);

        EventManager::instance()->on(new ScheduleMonitorListener());
    }

    /**
     * Add commands for the plugin.
     *
     * @param \Cake\Console\CommandCollection $commands The command collection to update.
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        $commands = parent::console($commands);

        $commands->add('schedule run', ScheduleRunCommand::class);
        $commands->add('schedule work', ScheduleWorkCommand::class);
        $commands->add('schedule clear', ScheduleClearCacheCommand::class);
        $commands->add('schedule finish', ScheduleFinishCommand::class);
        $commands->add('schedule test', ScheduleTestCommand::class);
        $commands->add('schedule list', ScheduleListCommand::class);

        $commands->add('schedule monitor sync', ScheduleMonitorSyncCommand::class);
        $commands->add('schedule monitor list', ScheduleMonitorListCommand::class);
        $commands->add('schedule monitor prune', ScheduleMonitorPruneCommand::class);

        return $commands;
    }

    /**
     * Register application container services.
     *
     * @param \Cake\Core\ContainerInterface $container The Container to update.
     * @return void
     * @link https://book.cakephp.org/5/en/development/dependency-injection.html#dependency-injection
     */
    public function services(ContainerInterface $container): void
    {
        $container->addShared(Schedule::class);

        $container
            ->add(ScheduleListCommand::class)
            ->addArgument(Schedule::class)
            ->addArgument(CommandFactoryInterface::class);

        $container
            ->add(ScheduleRunCommand::class)
            ->addArgument(Schedule::class)
            ->addArgument(CommandFactoryInterface::class);

        $container
            ->add(ScheduleWorkCommand::class)
            ->addArgument(Schedule::class)
            ->addArgument(CommandFactoryInterface::class);

        $container
            ->add(ScheduleTestCommand::class)
            ->addArgument(Schedule::class)
            ->addArgument(CommandFactoryInterface::class);

        $container
            ->add(ScheduleClearCacheCommand::class)
            ->addArgument(Schedule::class)
            ->addArgument(CommandFactoryInterface::class);

        $container
            ->add(ScheduleFinishCommand::class)
            ->addArgument(Schedule::class)
            ->addArgument(CommandFactoryInterface::class);

        $container
            ->add(ScheduleMonitorSyncCommand::class)
            ->addArgument(Schedule::class)
            ->addArgument(CommandFactoryInterface::class);

        $container
            ->add(ScheduleMonitorListCommand::class)
            ->addArgument(Schedule::class)
            ->addArgument(CommandFactoryInterface::class);
    }
}
