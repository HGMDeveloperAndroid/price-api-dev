<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Theme extends Model
{
    protected $table = 'theme';
    protected $fillable = ['logo_path', 'dark_theme', 'text', 'wallpaper', 'primary_button', 'secondary_button', 'primary_text', 'secondary_text', 'font'];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

}