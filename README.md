# PHP Artisan Powertools
A set of Laravel Artisan commands to automate common tasks.

## Installation
Currently, installation must be done manually. Add the following to the `composer.json` in the root directory of your Laravel project:
```
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/seb-jones/php-artisan-powertools"
        },
    ],
```

Then run:
```
composer require --dev sebj/php-artisan-powertools
```

## Available Commands

This package adds the following commands to `php artisan`

* `make:crud`
* `db:reseed`
* `relate`

It also adds a Blade directive:

* `@dd`

Below are brief descriptions of each component. For more information please
type `php artisan help command`.

### make:crud
Generates everything needed for a CRUD resource: model, controller, factory, seeder, migration, **views**. There is even an option to insert a `Route::resource` call in your `routes\web.php` file. Multiple model names can be specified to quickly generate the boilerplate for all your models.

### db:reseed
Truncates all the tables in the database except `migrations`, and then calls `php artisan db:seed`. Useful if you have a lot of migrations and often call `php artisan migrate:fresh --seed`.

### relate
Generates the methods and migrations needed for the specified relation. For example:

```
php artisan relate User hasMany Post
```

This will add a `hasMany` method to `\App\User`, a `belongsTo` method to `\App\Post`, and a migration that adds a `user_id` column to the `posts` table.

`relate` accepts arguments in any order, always reading model names left-to-right. So this would also work:

```
php artisan relate hasMany User Post
```

This means you can alias the commands nicely. For example, I have them aliased to `parho`, `parhm` and  `parbtm`.

### @dd

Lets you use `dd` in blade templates. This is essentially just a shorthand of:

```
@php dd() @endphp
```

I find myself typing the above code often enough that it seems to warrant it's own directive.
