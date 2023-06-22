<?php


namespace App\Repositories;

use App\Label;
use Illuminate\Http\Request;

class LabelRepository
{
    public function Filters(Request $request) {
        $region = Label::query();
        $region->when($request->textSearch, function ($q, $textSearch){
            $q->whereLike('name', $textSearch);
            $q->whereLike('alias', $textSearch);
            $q->whereLike('description', $textSearch);
        });

        $region->orderBy('name', 'ASC');
        return $region;
    }
}
