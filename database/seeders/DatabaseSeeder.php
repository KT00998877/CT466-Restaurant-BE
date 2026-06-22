<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
   
        // Gọi các seeder bạn muốn chạy ở đây
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            CategorySeeder::class,
            MenuItemSeeder::class,
            TableListSeeder::class,
            UserSeeder::class,
            IngredientSeeder::class,
            RecipeSeeder::class,
            
        ]); 
    }
    
}
