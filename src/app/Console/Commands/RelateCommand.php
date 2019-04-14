<?php

namespace PhpArtisanPowertools\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RelateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'relate {model1} {relationship} {model2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates the methods and migrations for a relationship between two models.';

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
        $relationship = camel_case($this->argument('relationship'));

        if (!in_array($relationship, ['hasOne', 'hasMany', 'belongsToMany'])) {
            $this->error('Relate accepts the following as it\'s second argument: hasOne, hasMany, belongsToMany');
            return;
        }

        $modelA = studly_case($this->argument('model1'));
        $modelB = studly_case($this->argument('model2'));

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
        $methodName = camel_case($relatee);
        if ($relationship === 'hasMany' || $relationship === 'belongsToMany') {
            $methodName = str_plural($methodName);
        }

        $method = <<<METHOD

    public function $methodName()
    {
        \$this->$relationship('\\App\\$relatee');
    }

METHOD;

        $fileContents = File::get($this->modelPath($relater));

        $lastBracePos = strrpos($fileContents, '}');

        $fileContents = substr_replace($fileContents, $method, $lastBracePos, 0);

        $this->info($fileContents);
    }

    private function modelPath($model)
    {
        return app_path($model . '.php');
    }

    private function createForeignKeyMigration($relater, $relatee)
    {
        $relater = snake_case(str_singular($relater));
        $relatee = snake_case(str_plural($relatee));

        // TODO make sure we have the right format e.g. leading zeros, etc.
        $timestamp = date('Y_m_d_His');

        $migrationName = "add_${relater}_id_to_${relatee}_table";

        $this->call('make:migration', [
            'name' => $migrationName,
            '--table' => $relatee,
        ]);

        $filename = "${timestamp}_$migrationName.php";

        $fileContents = File::get(database_path("migrations/$filename"));

        $fileContents = Str::replaceArray('//', [
            '$this->unsignedInteger(\'' . $relater . '_id\');',
            '$this->dropColumn(\'' . $relater . '_id\');',
        ], $fileContents);

        $this->info($fileContents);
    }

    private function createPivotTableMigration($relater, $relatee)
    {
    }
}
