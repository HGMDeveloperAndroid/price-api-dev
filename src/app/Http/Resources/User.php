<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Scans;
use App\Missions;
use App\Settings;
use App\Languages;
use Illuminate\Support\Facades\DB;

class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $labels = [];
        $regions = [];

        foreach ($this->labels as $label){
            $labels[] = $label->name;
        }

        foreach ($this->regions as $region) {
            $regions[] = $region->name;
        }

        $participated_missions = Scans::select('id_mission')->where('id_scanned_by', $this->id)
            ->groupBy('id_mission')
            ->get();
        $count_missions = 0;

        foreach ($participated_missions as $participated) {
            $count_missions ++;
        }

        $zone_users =  DB::table('zone_users')->where('id_user', $this->id)->first();
        $total_missions =  DB::table('zone_missions')
            ->join('missions', 'missions.id', 'zone_missions.id_mission')
            ->where('zone_missions.id_zone', $zone_users->id_zone)
            ->whereNull('missions.deleted_at')
            ->orderBy('zone_missions.id_mission', 'DESC')->count();
        $captures_made = Scans::where('id_scanned_by', $this->id)->count();
        $validated_captures = Scans::where('id_scanned_by', $this->id)
            ->where('is_valid', 1)
            ->where('is_rejected', 0)
            ->count();
        $scan_rejected = Scans::select(DB::raw('rejection_criteria.criterion, count(*) as total'))
            ->where('id_scanned_by', $this->id)
            ->join('rejection_criteria', 'rejection_criteria.id', 'scans.id_criterion')
            ->where('is_valid', 0)
            ->where('is_rejected', 1)
            ->groupBy('rejection_criteria.criterion')
            ->orderBy('rejection_criteria.id')
            ->get();
        $scan_rejected_count = Scans::where('id_scanned_by', $this->id)
            ->where('is_valid', 0)
            ->where('is_rejected', 1)
            ->count();

        $settings = Settings::where('id', 1)->first();
        $language = Languages::where('id', $this->lang_id)->first();
        $theme =  DB::table('theme')->first();

        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'mother_last_name' => $this->mother_last_name,
            'employee_number' => $this->employee_number,
            'cellphone' =>$this->cellphone,
            'picture_path' => $this->picture_path,
            'roles' => $this->getRoleNames(),
            'labels' => $labels,
            'regions' => $regions,
            'total_missions' => $total_missions + 1,
            'dark_theme' => $this->dark_theme,
            'participated_missions' => $count_missions,
            'captures_made' => $captures_made,
            'validated_captures' => $validated_captures,
            'rejected_count' => $scan_rejected_count,
            'rejected' => $scan_rejected,
            'language' => new Language($language),
            'settings' => new Setting($settings),
            'theme' => $theme
        ];
    }
}
