<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Languages extends Model
{

    use SoftDeletes;

    protected $table = 'languages';
    protected $fillable = ['name', 'abbreviation'];

    protected $hidden = [
        'deleted_at'
    ];

}