<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LabelsUser extends Model
{
    protected $table = 'labels_user';
    protected $fillable = ['id_user', 'id_label'];

    public function label()
    {
        return $this->belongsTo('Label', 'id_lable', 'id');
    }
}
