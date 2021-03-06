<?php

namespace PhpArtisanPowertools\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Arr;

class CrudMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud 
                            {names* : One or more names of Models to be generated} 
                            {--route : Whether a Resource::route call should be added to routes/web.php}
                            {--only=*} : Generate views and code for these actions only}
                            {--except=*} : Generate views and code for all but these actions}
                            {--extend=} : Extend generated views from this blade}
                            {--section=content : The name of the section generated by --extend}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create all boilerplate files and code for a CRUD Resource.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!empty($this->option('only')) && !empty($this->option('except'))) {
            $this->comment('Both --only and --except options were given. Ignoring --except option.');
        }

        foreach ($this->argument('names') as $name) {
            $name = Str::studly($name);
            $this->makeCrud($name);
            $this->comment("CRUD Resource '$name' created successfully.");
        }
    }

    public function makeCrud($name)
    {
        $this->call('make:model', [
            'name' => $name,
            '--all' => true,
            '--resource' => true,
        ]);

        $this->call('make:seeder', [
            'name' => $name . 'Seeder',
        ]);

        $this->createViews($name);

        $this->replaceControllerMethodBodies($name);

        if ($this->option('route')) {
            $this->appendRoute($name);
        }
    }

    public function createViews($name)
    {
        $directory = Str::kebab(Str::plural($name));
        $path = resource_path("views/$directory");

        if (!File::isDirectory($path)) {
            File::makeDirectory($path);
        }

        $this->createView('index', $name, $path);
        $this->createView('create', $name, $path);
        $this->createView('show', $name, $path);
        $this->createView('edit', $name, $path);
    }

    public function createView($bladeName, $modelName, $path)
    {
        if (!$this->shouldMakeView($bladeName)) {
            return;
        }

        $contents = File::get(__DIR__  . "/stubs/$bladeName.stub");

        $contents = str_replace('dummy', Str::camel($modelName), $contents);
        $contents = str_replace('dummies', Str::camel(Str::plural($modelName)), $contents);

        if ($this->option('extend')) {
            $extend = $this->option('extend');
            $section = $this->option('section');

            $contents = "@extends('$extend')\n\n@section('$section')\n\n$contents\n@endsection\n";
        }

        $fullpath = "$path/$bladeName.blade.php";

        File::put($fullpath, $contents);

        $this->info("View '$fullpath' created successfully.");
    }

    public function replaceControllerMethodBodies($name)
    {
        $path = app_path('Http/Controllers/');

        $studly = Str::studly($name);
        $camel = Str::camel($name);

        $contents = File::get($path . $studly . 'Controller.php');

        $pluralCamel = Str::camel(Str::plural($name));
        $pluralStudly = Str::studly(Str::plural($name));
        $routePrefix = Str::kebab(Str::plural($name));

        // Str::replaceArray doesn't replace if the replacement is the same as the searched string,
        // so we set empty comments to /**/ and replace them back afterward.
        $contents = Str::replaceArray('//', [
            $this->shouldMakeView('index') ? "return view(\"$routePrefix.index\")->with$pluralStudly($studly::all());" : '/**/',

            $this->shouldMakeView('create') ? "return view(\"$routePrefix.create\");" : '/**/',

            '/**/',

            $this->shouldMakeView('show') ? "return view(\"$routePrefix.show\")->with$studly(\$$camel);" : '/**/',

            $this->shouldMakeView('edit') ? "return view(\"$routePrefix.edit\")->with$studly(\$$camel);": '/**/',

            '/**/',

            '/**/',
        ], $contents);

        $contents = str_replace('/**/', '//', $contents);

        $fullpath = $path . $studly . 'Controller.php';

        File::put($fullpath, $contents);

        $this->info("Controller '$fullpath' updated successfully.");
    }

    public function appendRoute($name)
    {
        $pluralCamel = Str::camel(Str::plural($name));
        $studly = Str::studly($name);

        $contents = "\n";
        $contents .= "Route::resource('$pluralCamel', '${studly}Controller')";

        $optionName = '';
        $partials = [];
        if (!empty($this->option('only'))) {
            $optionName = 'only';
            $partials = $this->option($optionName);
        }
        else if (!empty($this->option('except'))) {
            $optionName = 'except';
            $partials = $this->except($this->option('except'));
        }

        if (!empty($partials)) {
            $contents .= "->$optionName([\n";

            foreach ($partials as $partial) {
                $contents .= "    '$partial',\n";
            }

            $contents .= '])';
        }

        $contents .= ";\n";

        File::append('routes/web.php', $contents);

        $this->info('Routes updated successfully.');
    }

    private function shouldMakeView($name)
    {
        if (!empty($this->option('only'))) {
            return in_array($name, $this->option('only'));
        }
        else if (!empty($this->option('except'))) {
            return !in_array($name, $this->option('except'));
        }

        return true;
    }

    private function except($except)
    {
        $views = [
            'index',
            'create',
            'show',
            'edit',
        ];

        return Arr::where($views, function ($value, $key) use ($except) {
            return !in_array($value, $except);
        });
    }
}
