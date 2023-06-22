<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lines extends Model
{
	use SoftDeletes;

    protected $table = 'lines';
    protected $fillable = ['id_group', 'name', 'description', 'lang_id'];
    protected $dates = ['deleted_at'];

    protected $hidden = [
        'deleted_at', 'lang_id'
    ];
}
