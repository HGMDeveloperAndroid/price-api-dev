<?php

namespace App;

use DB;
use App\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable, SoftDeletes;

    public $guard_name = 'api';

    protected $dates = ['deleted_at'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'email', 'password', 'first_name', 'last_name', 'mother_last_name', 'employee_number', 'cellphone', 'default_password','dark_theme', 'lang_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'default_password'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function labels()
    {
        return $this->belongsToMany('App\Label', 'labels_users', 'id_user', 'id_label');
    }

    public function regions()
    {
        return $this->belongsToMany('App\Region', 'zone_users', 'id_user', 'id_zone');
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    public function scans()
    {
        return $this->hasOne('App\Scans', 'id_scanned_by', 'id');
    }

    public function reviews()
    {
        return $this->hasOne('App\Scans', 'id_reviewed_by', 'id');
    }

    public function scopeScansValid($query, $idUser)
    {
        $query->where('id', $idUser);
        return $query->count();
    }

    public function points($id)
    {
        $users = UserPoints::query();
        $users->where('id_user', $id);

        return $users;
    }

    public function scansUser($id)
    {
        $scans = Scans::query();
        $scans->where('id_scanned_by', $id);

        return $scans;
    }

    public function pointsMission($id)
    {
        $scans = Scans::query();
        $scans->select(DB::raw('missions.mission_points + (count(scans.id) * missions.capture_points) as points'))
            ->where('id_scanned_by', $id)
            ->where('is_valid', 1)
            ->join('missions', 'scans.id_mission', 'missions.id')
            ->groupby('id_scanned_by', 'missions.capture_points', 'missions.mission_points');

        return $scans;
    }
}
