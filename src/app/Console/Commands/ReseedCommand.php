<?php

namespace PhpArtisanPowertools\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;

class ReseedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:reseed {--delete : Use the DELETE SQL Command instead of the TRUNCATE SQL Command. This can be faster, but will NOT reset auto-incrementing columns. Use at your own risk.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Empties all tables (except migrations) and calls db:seed.';

    /**
     * Create a new command instance.
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
     * @return mixed
     */
    public function handle()
    {
        Schema::disableForeignKeyConstraints();

        $tables = DB::select('SHOW TABLES');

        foreach ($tables as $table) {
            $table = (array)$table;
            $key = array_keys($table)[0];

            if ($table[$key] !== "migrations") {
                $this->info("Truncating table " . $table[$key]);

                if ($this->option('delete')) {
                    DB::table($table[$key])->delete();
                }
                else {
                    DB::table($table[$key])->truncate();
                }
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->call('db:seed');
    }
}
