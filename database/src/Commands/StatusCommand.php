<?php

namespace Dev\Database\Commands;

use Dev\Database\Migrations\Migrator;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputOption;

class StatusCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'tt-migrate:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each migration';

    /**
     * The migrator instance.
     *
     * @var \Dev\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration rollback command instance.
     *
     * @param  \Dev\Database\Migrations\Migrator $migrator
     * @return void
     */
    public function __construct(Migrator $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->migrator->setConnection($this->option('database'));

        if (! $this->migrator->repositoryExists()) {
            return $this->error('Migration table not found.');
        }

        $ran = $this->migrator->getRan();

        $batches = $this->migrator->getRepository()->getMigrationBatches();

        if (count($migrations = $this->getStatusFor($ran, $batches)) > 0) {
            $this->table(['Ran?', 'Migration', 'Batch'], $migrations);
        } else {
            $this->error('No migrations found');
        }
    }

    /**
     * Get the status for the given ran migrations.
     *
     * @param  array  $ran
     * @param  array  $batches
     * @return \Illuminate\Support\Collection
     */
    protected function getStatusFor(array $ran, array $batches)
    {
        return Collection::make($this->getAllMigrationFiles())
                    ->map(function ($migration) use ($ran, $batches) {
                        $migrationName = $this->migrator->getMigrationName($migration);

                        return in_array($migrationName, $ran)
                                ? ['<info>Yes</info>', $migrationName, $batches[$migrationName]]
                                : ['<fg=red>No</fg=red>', $migrationName];
                    });
    }

    /**
     * Get an array of all of the migration files.
     *
     * @return array
     */
    protected function getAllMigrationFiles()
    {
        return $this->migrator->getMigrationFiles($this->getMigrationPaths());
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'],

            ['path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The path(s) to the migrations files to use'],

            ['realpath', null, InputOption::VALUE_NONE, 'Indicate any provided migration file paths are pre-resolved absolute paths'],
        ];
    }
}