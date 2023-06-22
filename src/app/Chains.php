<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chains extends Model
{
    use SoftDeletes;

    protected $table = 'chains';
    protected $fillable = ['name', 'alias', 'description', 'lang_id', 'logo_path', 'is_notificable']; 
    protected $dates = ['deleted_at'];

    protected $hidden = [
        'deleted_at', 'lang_id'
    ];
}
