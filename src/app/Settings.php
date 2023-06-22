<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Settings extends Model
{   
    protected $table = 'settings';
    protected $fillable = ['logo_path', 'lang_id'];

    protected $hidden = [
        'id','deleted_at', 'created_at', 'updated_at'
    ];

}