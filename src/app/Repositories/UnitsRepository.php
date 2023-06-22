<?php


namespace App\Repositories;


use App\Units;
use Illuminate\Http\Request;

class UnitsRepository
{


    public function Filters(Request $request)
    {
        $units = Units::query()->select('id', 'name');
        $units->when($request->textSearch, function ($q, $textSearch) {
            $q->whereLike('name', $textSearch);
        });

        $units->orderBy('name', 'ASC');
        return $units;
    }

}
