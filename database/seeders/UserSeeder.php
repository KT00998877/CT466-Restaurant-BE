<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Xóa data cũ trước để tránh trùng
        User::whereIn('email', [
            'admin@gmail.com',
            'cashier@gmail.com',
            'waiter@gmail.com',
            'kitchen@gmail.com',
            'customer@gmail.com',
        ])->delete();

        $roles = Role::whereIn('name', ['admin', 'cashier', 'waiter', 'kitchen', 'user'])
            ->pluck('id', 'name');

        User::create([
            'name'     => 'Quản Trị Viên',
            'email'    => 'admin@gmail.com',
            'password' => Hash::make('123456'),
            'role'     => 'admin',
            'role_id'  => $roles['admin'] ?? null,
            'phone'    => '0901111111',
        ]);

        User::create([
            'name'     => 'Thu Ngân',
            'email'    => 'cashier@gmail.com',
            'password' => Hash::make('123456'),
            'role'     => 'cashier',
            'role_id'  => $roles['cashier'] ?? null,
            'phone'    => '0902222222',
        ]);

        User::create([
            'name'     => 'Phục Vụ',
            'email'    => 'waiter@gmail.com',
            'password' => Hash::make('123456'),
            'role'     => 'waiter',
            'role_id'  => $roles['waiter'] ?? null,
            'phone'    => '0903333333',
        ]);

        User::create([
            'name'     => 'Bếp',
            'email'    => 'kitchen@gmail.com',
            'password' => Hash::make('123456'),
            'role'     => 'kitchen',
            'role_id'  => $roles['kitchen'] ?? null,
            'phone'    => '0905555555',
        ]);

        User::create([
            'name'     => 'Khách Hang',
            'email'    => 'customer@gmail.com',
            'password' => Hash::make('123456'),
            'role'     => 'user',
            'role_id'  => $roles['user'] ?? null,
            'phone'    => '0904444444',
        ]);
    }
}
