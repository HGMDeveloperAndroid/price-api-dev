<?php

use Illuminate\Database\Seeder;
use App\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UsersDefaultPasswordTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $password = bcrypt('asd123');

        $user = User::create([
            'username' => 'ricardo',
            'password' => $password,
            'default_password' =>'',
            'first_name' => 'Ricardo',
            'last_name' => '',
            'email' => 'ricardo@gmail.com',
        ]);

        $user2 = User::create([
            'username' => 'alice',
            'password' => $password,
            'default_password' =>'',
            'first_name' => 'Alice',
            'last_name' => '',
            'email' => 'alice@bnom.io ',
        ]);

//        $roleValidator = Role::findByName('Validator', 'api');
        $roleAdmin = Role::findByName('Admin', 'api');
        $permissionsAdmin = Permission::pluck('id', 'id')->all();
//        $permissionsValidator = [5=>5, 6=>6,7=>7, 8=>8];

//        $roleValidator->syncPermissions($permissionsValidator);
//        $userValidator->assignRole($roleValidator);

        $roleAdmin->syncPermissions($permissionsAdmin);
        $user->assignRole($roleAdmin);

        $user2->assignRole($roleAdmin);

    }
}
