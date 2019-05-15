<?php

use Faker\Generator as Faker;
use Gogol\Admin\Tests\App\Models\Articles\Article;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(Article::class, function (Faker $faker) {
    return [
        'name' => $faker->title,
        'content' => $faker->paragraph,
    ];
});
