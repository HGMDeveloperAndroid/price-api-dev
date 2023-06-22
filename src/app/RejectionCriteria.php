<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RejectionCriteria extends Model
{

    protected $table = 'rejection_criteria';
    protected $fillable = ['criterion'];
}