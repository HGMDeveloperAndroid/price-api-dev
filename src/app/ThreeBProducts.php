<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ThreeBProducts  extends Model
{
    protected $fillable = [
        'item',
        'keycode',
        'barcode',
        'description',
        'unit_quantity',
        'type',
        'unit_id',
        'price',
        'status'
    ];
}
