<?php

use Faker\Generator as Faker;
use Gogol\Admin\Tests\App\Models\Fields\FieldsType;

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

$factory->define(FieldsType::class, function (Faker $faker) {
    return [
        'string' => $faker->text,
        'text' => $faker->paragraph,
        'editor' => '<p>'.$faker->paragraph.'</p>',
        'select' => $faker->randomElement(['option a', 'option b']),
        'integer' => $faker->randomNumber(),
        'decimal' => $faker->randomFloat(2, -1000, 1000),
        'file' => $faker->randomElement(['image1.jpg', 'image2.jpg', 'image3.jpg']),
        'password' => str_random(10),
        'date' => $faker->date(),
        'datetime' => $faker->dateTime(),
        'time' => $faker->time(),
        'checkbox' => $faker->randomElement([1, 0]),
        'radio' => $faker->randomElement(['a', 'b', 'c']),
        'custom' => $faker->text,
    ];
});
