<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'description' => 'Quản trị viên'],
            ['name' => 'cashier', 'description' => 'Nhân viên thu ngân'],
            ['name' => 'waiter', 'description' => 'Nhân viên phục vụ'],
            ['name' => 'kitchen', 'description' => 'Nhân viên bếp'],
            ['name' => 'user', 'description' => 'Khách hàng'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
