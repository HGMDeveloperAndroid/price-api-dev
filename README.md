# pricecheck-apiv2

### Install framework
`composer install`

### Configure settings in .env file
`cp .env.example .env`

### Generate key: 

`php artisan key:generate`

### Run migrations:

`php artisan migrate`

### Generate encryption keys

`php artisan passport:install`

### Run seeders:
```
php artisan db:seed --class=PermissionTableSeeder
php artisan db:seed --class=ZoneSeeder
php artisan db:seed --class=CreateUserSeeder
php artisan db:seed --class=UsersDefaultPasswordTableSeeder
php artisan db:seed --class=BrandCopySeeder
php artisan db:seed --class=UnitSeeder
php artisan db:seed --class=GroupSeeder
php artisan db:seed --class=LineSeeder
```
