<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Label;
use Faker\Generator as Faker;

$factory->define(Label::class, function (Faker $faker) {
    $alias = $faker->citySuffix;

    return [
        'name' => $faker->city,
        'alias' => $alias,
        'description' => $faker->text,
        'short_name' => Str::slug($alias)
    ];
});
