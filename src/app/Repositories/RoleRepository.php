<?php

namespace App\Repositories;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleRepository
{

    public function Filters(Request $request)
    {
        $roles = Role::query();
        $roles->select('id', 'name');
        $roles->when($request->textSearch, function ($q, $textSearch){
            $q->whereLike('name', $textSearch);
        });

        $roles->orderBy('name', 'ASC');
        return $roles;
    }
}
