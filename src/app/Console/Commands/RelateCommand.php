<?php

namespace PhpArtisanPowertools\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PhpArtisanPowertools\ClassName;

class RelateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'relate {model1} {relationship} {model2} {--legacy : Use \'unsignedInteger\' instead of \'unsignedBigInteger\' for foreign keys in migrations.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates the methods and migrations for a relationship between two models.';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $relationship = Str::camel($this->argument('relationship'));

        if (!in_array($relationship, ['hasOne', 'hasMany', 'belongsToMany'])) {
            $this->error('Relate accepts the following as its second argument: hasOne, hasMany, belongsToMany');
            return;
        }

        $modelA = ClassName::absolute($this->argument('model1'));
        $modelB = ClassName::absolute($this->argument('model2'));

        if (!File::exists($this->modelPath($modelA))) {
            $this->error("Model '$modelA' does not exist.");
            return;
        }

        if (!File::exists($this->modelPath($modelB))) {
            $this->error("Model '$modelB' does not exist.");
            return;
        }

        switch ($relationship) {
        case 'hasOne':
            $this->insertRelationshipMethod($modelA, 'hasOne', $modelB);
            $this->insertRelationshipMethod($modelB, 'belongsTo', $modelA);
            $this->createForeignKeyMigration($modelA, $modelB);
            break;
        case 'hasMany':
            $this->insertRelationshipMethod($modelA, 'hasMany', $modelB);
            $this->insertRelationshipMethod($modelB, 'belongsTo', $modelA);
            $this->createForeignKeyMigration($modelA, $modelB);
            break;
        case 'belongsToMany':
            $this->insertRelationshipMethod($modelA, 'belongsToMany', $modelB);
            $this->insertRelationshipMethod($modelB, 'belongsToMany', $modelA);
            $this->createPivotTableMigration($modelA, $modelB);
            break;
        }
    }

    private function insertRelationshipMethod($relater, $relationship, $relatee)
    {
        $methodName = Str::camel(ClassName::class($relatee));
        if ($relationship === 'hasMany' || $relationship === 'belongsToMany') {
            $methodName = Str::plural($methodName);
        }

        $method = <<<METHOD

    public function $methodName()
    {
        return \$this->$relationship($relatee::class);
    }

METHOD;

        $fileContents = File::get($this->modelPath($relater));

        $lastBracePos = strrpos($fileContents, '}');

        $fileContents = substr_replace($fileContents, $method, $lastBracePos, 0);

        File::put($this->modelPath($relater), $fileContents);
    }

    private function modelPath($model)
    {

        return app_path(str_replace('\\App\\', '', $model) . '.php');
    }

    private function createForeignKeyMigration($relater, $relatee)
    {
        $relater = Str::snake(Str::singular(ClassName::class($relater)));
        $relatee = Str::snake(Str::plural(ClassName::class($relatee)));

        $timestamp = date('Y_m_d_His');

        $migrationName = "add_${relater}_id_to_${relatee}_table";

        $this->call('make:migration', [
            'name' => $migrationName,
            '--table' => $relatee,
        ]);

        $filename = "migrations/${timestamp}_$migrationName.php";

        $fileContents = File::get(database_path($filename));

        if ($this->option('legacy')) {
            $foreignKeyType = 'unsignedInteger';
        }
        else {
            $foreignKeyType = 'unsignedBigInteger';
        }

        $fileContents = Str::replaceArray('//', [
            "\$table->$foreignKeyType('{$relater}_id');",
            "\$table->dropColumn('${relater}_id');",
        ], $fileContents);

        File::put(database_path($filename), $fileContents);
    }

    private function createPivotTableMigration($relater, $relatee)
    {
        $models = array_values(Arr::sort([
            Str::snake(Str::singular(ClassName::class($relater))),
            Str::snake(Str::singular(ClassName::class($relatee))),
        ]));

        // TODO make sure we have the right format e.g. leading zeros, etc.
        $timestamp = date('Y_m_d_His');

        $tableName = $models[0] . "_" . $models[1];

        $migrationName = "create_${tableName}_table";

        $this->call('make:migration', [
            'name' => $migrationName,
            '--create' => $tableName,
        ]);

        $filename = "migrations/${timestamp}_$migrationName.php";

        $fileContents = File::get(database_path($filename));

        if ($this->option('legacy')) {
            $foreignKeyType = 'unsignedInteger';
        }
        else {
            $foreignKeyType = 'unsignedBigInteger';
        }

        $fileContents = preg_replace_callback(
            '/\$table-\>timestamps\(\);/',
            function ($matches) use ($models, $foreignKeyType) {
                return 
                    "\$table->$foreignKeyType('${models[0]}_id');\n" .
                    "            \$table->$foreignKeyType('${models[1]}_id');\n" . 
                    "            " . $matches[0];
            },
            $fileContents
        );

        File::put(database_path($filename), $fileContents);
    }
}
