<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Units extends Model
{
    use SoftDeletes;

    protected $table = 'units';
    protected $fillable = ['name', 'lang_id'];
    protected $dates = ['deleted_at'];

    protected $hidden = [
        'deleted_at', 'lang_id'
    ];
}
