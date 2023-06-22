<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Groups extends Model
{
	use SoftDeletes;

    protected $table = 'groups';
    protected $fillable = ['name', 'description', 'lang_id'];
    protected $dates = ['deleted_at'];

    protected $hidden = [
        'deleted_at', 'lang_id'
    ];

}
