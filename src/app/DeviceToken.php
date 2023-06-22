<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $table = 'device_token';
    protected $fillable = ['id_user', 'device_token'];
}
