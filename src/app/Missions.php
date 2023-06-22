<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Missions extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'title', 'description', 'mission_points', 'capture_points', 'start_date', 'end_date'
    ];

    public function createdBy()
    {
        return $this->belongsTo('App\User', 'created_by', 'id');
    }

    public function regions()
    {
        return $this->belongsToMany('App\Region', 'zone_missions', 'id_mission', 'id_zone');
    }

    public function scans()
    {
        return $this->hasMany('App\Scans', 'id_mission', 'id');
    }

}
