<?php

namespace Dev\Database\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Dev\Database\Builder;
use Dev\Tarantool\Connection;

class InstallCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'tt-migrate:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the migration repository';

    /**
     * Create a new migration install command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        /**
         * @var $connection Connection
         */
        $builder = new Builder();

        $builder->create('migrations')
            ->addColumn('id', 'unsigned', true, true, true)
            ->addColumn('batch', 'string', false, false, true)
            ->addColumn('migration', 'string', false, false, true);

        $this->info('Migration table created successfully.');
    }
}
