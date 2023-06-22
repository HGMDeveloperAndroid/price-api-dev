<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Label extends Model
{
    use SoftDeletes;

    protected $table = 'labels';
    protected $fillable = ['name', 'short_name', 'alias', 'description'];
    protected $dates = ['deleted_at'];
}
