<?php

namespace App;


use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Database\Eloquent\Model;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SpatialTrait, SoftDeletes;

    protected $dates =['deleted_at'];
    protected $spatialFields = ['location'];
    protected $fillable = ['name', 'address', 'phone', 'location'];

    public function scopeLocation($query, $lat, $lng)
    {
        $geometry = New Point($lat, $lng);
        $query->contains('location', $geometry);

        return $query;
    }

    public function getCoordinates()
    {
        $coordinates =[
          'latitude' => $this->location->getLatitude(),
          'longitude' => $this->location->getLongitude()
        ];
    }
}
