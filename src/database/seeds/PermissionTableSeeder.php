<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',
            'label-list',
            'label-create',
            'label-edit',
            'label-delete',
            'scanner-list',
            'scanner-create',
            'scanner-edit',
            'scanner-delete',
            'user-list',
            'user-register',
            'user-update',
            'user-trash',
            'user-restore',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'api']);
        }
    }
}
