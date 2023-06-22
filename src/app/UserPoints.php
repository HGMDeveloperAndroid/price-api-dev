<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserPoints extends Model
{
    protected $fillable = ['id_mission', 'id_user', 'reason', 'amount'];
}
