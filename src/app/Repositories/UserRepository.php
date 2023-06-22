<?php


namespace App\Repositories;

use App\User;
use Illuminate\Http\Request;

class UserRepository
{

    public function Filters(Request $request)
    {
        $users = User::query();

        $users->when($request->textSearch, function ($q, $textSearch) {
            $q->whereLike('username', $textSearch)
                ->whereLike('first_name', $textSearch)
                ->whereLike('last_name', $textSearch)
                ->whereLike('mother_last_name', $textSearch)
                ->whereLike('employee_number', $textSearch)
                ->whereLike('email', $textSearch);
        });

        $users->when($request->role, function ($q, $role) {
            $q->role($role);
        });

        if ($request->filled('region')) {
            $region = $request->region;
            $users->whereHas('regions', function ($q) use ($region) {
                $q->where('name', $region);
            });
        }

        $users->orderBy('created_at', 'ASC');
        return $users;
    }

    public function filterId(Request $request)
    {
        $users = User::query();
        $users->where('id', $request->id);

        return $users;
    }
}
