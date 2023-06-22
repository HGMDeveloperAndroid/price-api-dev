<?php

/** @var Factory $factory */

use App\Region;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Str;

$factory->define(Region::class, function (Faker $faker) {
    $alias = $faker->citySuffix;

    return [
        'name' => $faker->city,
        'alias' => $alias,
        'description' => $faker->text,
        'short_name' => Str::slug($alias)
    ];
});
