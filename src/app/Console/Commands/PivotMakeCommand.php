<?php

namespace PhpArtisanPowertools\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class PivotMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:pivot 
                            {model1} 
                            {model2} 
                            {--migrate : Whether to run a migration when this command finishes.}
                            {--add-methods : Whether to insert belongsToMany relationship methods in the respective models.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a migration for a pivot table.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $models = Arr::sort([
            snake_case(str_singular($this->argument('model1'))),
            snake_case(str_singular($this->argument('model2'))),
        ]);

        $table_name = $models[0] . '_' . $models[1];

        // TODO make sure we have the right format e.g. leading zeros, etc.
        $timestamp = date('Y_m_d_His');

        $this->call('make:migration', [
            'name' => "create_${table_name}_table",
            '--create' => $table_name,
        ]);

        $filename = "${timestamp}_create_${table_name}_table.php";

        $contents = File::get(database_path("migrations/$filename"));

        $contents = preg_replace_callback(
            '/\$table-\>timestamps\(\);/',
            function ($matches) use ($models) {
                return 
                    "\$table->unsignedInteger('${models[0]}_id');\n" .
                    "            \$table->unsignedInteger('${models[1]}_id');\n" . 
                    "            " . $matches[0];
            },
            $contents
        );

        File::put(database_path("migrations/$filename"), $contents);

        if ($this->option('add-methods')) {
            foreach ($models as $model) {
                $studly = studly_case($model);
                if (!File::exists(app_path("$studly.php"))) {
                    $this->comment("Model $studly not found. Creating.");
                    $this->call("make:model", [
                        'name' => $studly
                    ]);
                }
            }

            $this->addRelationshipMethod($models[0], $models[1]);
            $this->addRelationshipMethod($models[1], $models[0]);
        }

        if ($this->option('migrate')) {
            $this->call('migrate');
        }
    }

    private function addRelationshipMethod($model1, $model2)
    {
        $path = app_path(studly_case($model1) . ".php");

        $contents = File::get($path);
        if (!$contents) {
            return;
        }

        $methodName = camel_case(str_plural($model2));
        $className = studly_case(str_singular($model2));
        $closingBracePos = strrpos($contents, '}');
        $contents = substr_replace($contents, 
            <<<CODE

    public function $methodName()
    {
        return \$this->belongsToMany('App\\$className');
    }

CODE
        ,
            $closingBracePos, 
            0);

        File::put($path, $contents);
    }
}
