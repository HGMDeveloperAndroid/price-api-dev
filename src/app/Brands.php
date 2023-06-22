<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brands extends Model
{
    use SoftDeletes;

    protected $table = 'brands';
    protected $fillable = ['name', 'description', 'lang_id'];
    protected $dates = ['deleted_at'];

    protected $hidden = [
        'deleted_at' , 'lang_id'
    ];
}
