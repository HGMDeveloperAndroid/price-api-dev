<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use SoftDeletes;

    protected $table = 'zones';
    protected $fillable = ['name', 'short_name', 'alias', 'description', 'lang_id'];
    protected $dates = ['deleted_at'];

    protected $hidden = [
        'deleted_at', 'lang_id'
    ];
}
