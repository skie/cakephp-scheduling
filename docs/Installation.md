# Installation

## Composer

```
composer require skie/cakephp-scheduling
```

## Load the Plugin

Ensure the Scheduling Plugin is loaded in your src/Application.php file

```
$this->addPlugin(\Scheduling\Plugin::class);
```

## Load SignalHandler Plugin (Required)

The Scheduling plugin requires the SignalHandler plugin for graceful termination support:

```
$this->addPlugin(\SignalHandler\Plugin::class);
```

The plugin automatically registers the scheduling services and integrates with CakePHP's console system and event manager. No additional configuration is required for basic scheduling functionality.

## Database Migrations

If you plan to use the monitoring features, run the database migrations to create the required tables:

```bash
bin/cake migrations migrate -p Scheduling
```

This will create the following tables:
- `monitored_scheduled_tasks` - stores task metadata and status
- `monitored_scheduled_task_log_items` - stores execution logs with metadata

## Platform Support

The plugin works on all platforms supported by CakePHP:

* **Linux**: Full support with all features
* **Windows**: Full support with all features
* **macOS**: Full support with all features

The plugin uses the SignalHandler plugin for cross-platform signal handling, ensuring consistent behavior across all operating systems.

## Requirements Verification

The plugin automatically checks for required dependencies:

* CakePHP 5.0+ framework
* PHP 8.4+ runtime
* SignalHandler plugin for graceful termination
* Access to system commands for shell execution tasks

If any requirements are missing, the plugin will provide clear error messages during initialization.

## Automatic Integration

The plugin automatically integrates with CakePHP's systems:

* Registers scheduling services in the dependency injection container
* Integrates with CakePHP's console command system
* Connects to the event manager for task lifecycle events
* Provides commands for running and managing scheduled tasks
* Sets up signal handling for graceful termination

No manual configuration is required for basic scheduling functionality.

## Configuration Options

The plugin supports optional configuration for advanced use cases:

* Cache configuration for task overlap prevention
* Timezone settings for scheduled tasks
* Event listener registration for custom monitoring
* Mutex implementations for single-server execution

For detailed configuration options, see the Integration documentation.

## Verification

To verify the plugin is installed correctly, run:

```
bin/cake --help
```

You should see the following scheduling commands available:

* `schedule run` - Run scheduled tasks (typically called by cron)
* `schedule work` - Run the scheduler continuously for development
* `schedule list` - List all scheduled tasks
* `schedule test` - Test scheduled task execution
* `schedule finish` - Finish a scheduled task execution
* `schedule clear` - Clear scheduling cache
* `schedule monitor list` - List all scheduled tasks
* `schedule monitor prune` - Prune scheduled tasks
* `schedule monitor sync` - Sync scheduled tasks

## Next Steps

After installation, proceed to the [Integration](Integration.md) guide to learn how to define and configure your scheduled tasks.
