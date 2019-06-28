<?php

use Faker\Generator as Faker;
use Admin\Tests\App\Models\Articles\Article;

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
        'content' => '<p>'.$faker->paragraph.'</p>',
        'score' => rand(0, 10),
        'image' => $faker->randomElement(['image1.jpg', 'image2.jpg', 'image3.jpg']),
    ];
});
