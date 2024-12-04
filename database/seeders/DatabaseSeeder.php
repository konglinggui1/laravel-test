<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminRole     = Role::create(['name' => 'admin']);
        $devPermission = Permission::create(['name' => 'admin']);

        $adminRole->givePermissionTo($devPermission);

        $admin = User::find(1);
        $admin->assignRole('admin');
    }
}
