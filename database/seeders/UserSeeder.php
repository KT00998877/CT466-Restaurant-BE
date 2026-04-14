<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
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

        User::create([
            'name'     => 'Quản Trị Viên',
            'email'    => 'admin@gmail.com',
            'password' => Hash::make('123456'),
            'role'     => 'admin',
            'phone'    => '0901111111',
        ]);

        User::create([
            'name'     => 'Thu Ngân',
            'email'    => 'cashier@gmail.com',
            'password' => Hash::make('123456'),
            'role'     => 'cashier',
            'phone'    => '0902222222',
        ]);

        User::create([
            'name'     => 'Phục Vụ',
            'email'    => 'waiter@gmail.com',
            'password' => Hash::make('123456'),
            'role'     => 'waiter',
            'phone'    => '0903333333',
        ]);

        User::create([
            'name'     => 'Bếp',
            'email'    => 'kitchen@gmail.com',
            'password' => Hash::make('123456'),
            'role'     => 'kitchen',
            'phone'    => '0905555555',
        ]);

        User::create([
            'name'     => 'Khách Hang',
            'email'    => 'customer@gmail.com',
            'password' => Hash::make('123456'),
            'role'     => 'user',
            'phone'    => '0904444444',
        ]);
    }
}
