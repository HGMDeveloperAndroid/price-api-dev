<?php

namespace App\Repositories;

use App\Missions;
use App\Scans;
use Illuminate\Http\Request;

class MissionRepository
{
    public function Filters(Request $request) {
        $mission = Missions::query();
        $mission->when($request->textSearch, function ($q, $textSearch){
            $q->whereLike('title', $textSearch);
        });

        $mission->orderBy('title', 'ASC');
        return $mission;
    }

    public function FiltersValidation() {
        $query = Scans::query();
        $query->select('id_mission')
            ->where('is_locked', 0)
            ->where('is_valid', 0)
            ->where('is_rejected', 0)
            ->join('users', 'users.id', 'scans.id_scanned_by')
            ->whereNull('users.deleted_at');

        $mission = Missions::query();
        $mission->whereIn('id', $query);

        $mission->orderBy('title', 'ASC');
        return $mission;
    }
}
