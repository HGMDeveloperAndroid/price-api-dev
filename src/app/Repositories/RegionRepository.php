<?php

namespace App\Repositories;

use App\Region;
use Illuminate\Http\Request;

class RegionRepository
{
    public function Filters(Request $request) {
        $region = Region::query();
        $region->when($request->textSearch, function ($q, $textSearch){
            $q->whereLike('name', $textSearch);
            $q->whereLike('alias', $textSearch);
            $q->whereLike('description', $textSearch);
        });

        $region->orderBy('name', 'ASC');
        return $region;
    }
}
