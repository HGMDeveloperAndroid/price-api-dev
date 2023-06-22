<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ListProducts extends Model
{
    protected $table = 'list_of_products';
    protected $fillable = ['id_user', 'id_product'];
}
