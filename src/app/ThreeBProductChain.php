<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThreeBProductChain extends Model
{

    protected $table = 'three_b_products_chains';
    protected $fillable = [
        'three_b_product_id',
        'chain_id',
        'barcode'
    ];
}
