<?php


namespace Dev\Database;


use Illuminate\Database\Console\Migrations\StatusCommand;
use Dev\Database\Commands\InstallCommand;
use Dev\Database\Commands\RollbackCommand;
use Dev\Database\Migrations\Migrator;
use Dev\Database\Commands\MigrateMakeCommand;
use Dev\Database\Commands\MigrateCommand;
use Illuminate\Support\ServiceProvider;
use Dev\Database\Migrations\MigrationCreator;

class DatabaseServiceProvider extends ServiceProvider
{

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'Migrate' => 'dev.command.migrate',
        'MigrateInstall' => 'dev.command.migrate.install',
        'MigrateRollback' => 'dev.command.migrate.rollback',
        'MigrateMake' => 'dev.command.migrate.make',
    ];

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {

    }

    public function register()
    {
        $this->registerRepositories();
        $this->registerMigrator();
        $this->registerCommands($this->commands);
    }

    /**
     * Register the given commands.
     *
     * @param  array  $commands
     * @return void
     */
    protected function registerCommands(array $commands)
    {
        foreach (array_keys($commands) as $command) {
            call_user_func_array([$this, "register{$command}Command"], []);
        }

        $this->commands(array_values($commands));
    }
    /**
     * Register the Repositories
     *
     * @return void
     */
    protected function registerRepositories()
    {
        $this->app->singleton('dev.migration.creator', function ($app) {
            return new MigrationCreator($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateCommand()
    {
        $this->app->singleton('dev.command.migrate', function ($app) {
            return new MigrateCommand($app['dev.migrator']);
        });
    }
    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateStatusCommand()
    {
        $this->app->singleton('dev.command.migrate.status', function ($app) {
            return new StatusCommand($app['dev.migrator']);
        });
    }
    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateInstallCommand()
    {
        $this->app->singleton('dev.command.migrate.install', function ($app) {
            return new InstallCommand();
        });
    }
    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateRollbackCommand()
    {
        $this->app->singleton('dev.command.migrate.rollback', function ($app) {
            return new RollbackCommand($app['dev.migrator']);
        });
    }

    /**
     * Register the migrator service.
     *
     * @return void
     */
    protected function registerMigrator()
    {
        // The migrator is responsible for actually running and rollback the migration
        // files in the application. We'll pass in our database connection resolver
        // so the migrator can resolve any of these connections when it needs to.
        $this->app->singleton('dev.migrator', function ($app) {
//            $repository = $app['migration.repository'];

            return new Migrator($app['db'], $app['files'], $app['events']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMigrateMakeCommand()
    {
        $this->app->singleton('dev.command.migrate.make', function ($app) {
            // Once we have the migration creator registered, we will create the command
            // and inject the creator. The creator is responsible for the actual file
            // creation of the migrations, and may be extended by these developers.
            $creator = $app['dev.migration.creator'];

            $composer = $app['composer'];

            return new MigrateMakeCommand($creator, $composer);
        });
    }
}
