<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PasswordResets extends Model
{
    protected $primaryKey = 'email';
    protected $fillable = ['email', 'token', 'created_at'];
    public $timestamps = false;
}
