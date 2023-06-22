<?php

use Illuminate\Database\Seeder;
use App\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $passwordAdmin = bcrypt('admin123');
        $userAdmin = User::create([
            'username' => 'pedrorojas',
            'password' => $passwordAdmin,
            'default_password' => '',
            'first_name' => 'Pedro',
            'last_name' => 'Rojas',
            'email' => 'pedro.rojas@gmail.com',
        ]);

        $passwordValidator = bcrypt('user1234');
        $userValidator = User::create([
            'username' => 'validatorUser',
            'password' => $passwordValidator,
            'default_password' => $passwordValidator,
            'first_name' => 'Fulanito',
            'last_name' => 'Yakul',
            'email' => 'fulanito1@gmail.com',
        ]);

        $passworScanner = bcrypt('user1234');
        $userScanner = User::create([
            'username' => 'scannerUser',
            'password' => $passworScanner,
            'default_password' => '',
            'first_name' => 'Scanner',
            'last_name' => 'User Last Name',
            'email' => 'fulanito2@gmail.com',
        ]);

        $roleAdmin = Role::create(['name' => 'Admin', 'guard_name' => 'api']);
        $roleValidator = Role::create(['name' => 'Validator', 'guard_name' => 'api']);
        $roleScanner = Role::create(['name' => 'Scanner', 'guard_name' => 'api']);

//        $userAdmin = User::find(2);
//        $userValidator = User::find(3);
//        $userScanner = User::find(4);
//
//        $roleAdmin = Role::findByName('Admin', 'api');
//        $roleValidator = Role::findByName('Validator', 'api');
//        $roleScanner = Role::findByName('Scanner', 'api');

        $permissionsAdmin = Permission::pluck('id', 'id')->all();
        $permissionsValidator = [5=>5, 6=>6,7=>7, 8=>8];
        $permissionsScanner = [9=>9, 10=>10,11=>11, 12=>12];

        $roleAdmin->syncPermissions($permissionsAdmin);
        $userAdmin->assignRole($roleAdmin);

        $roleValidator->syncPermissions($permissionsValidator);
        $userValidator->assignRole($roleValidator);

        $roleScanner->syncPermissions($permissionsScanner);
        $userScanner->assignRole($roleScanner);
    }
}
