<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    protected $table = 'notifications';
    protected $fillable = [
        'id_user',
        'notification_title',
        'body',
        'data_title',
        'description',
        'type',
        'dateTime',
        'active'
    ];
}
