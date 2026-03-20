<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TableListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Khai báo 3 khu vực
        $areas = ['A', 'B', 'C'];

        $tables = [];

        // Tạo 15 bàn (3 khu trực x 5 bàn mỗi khu trực)
        foreach ($areas as $area) {
            
            for ($i = 1; $i <= 5; $i++) {

                
                $capacity = 4; 
                if ($area === 'C') $capacity = 6;

                $tables[] = [
                    'name'       => $area . $i,
                    'capacity'   => $capacity,
                    'status'     => 'available', 
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
        }

        // lưu dữ liệu vào db bằng insert
        DB::table('table_lists')->insert($tables);
    }

    
}
