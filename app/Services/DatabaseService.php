<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PDO;

class DatabaseService
{
    /**
     * Gọi Stored Procedure và lấy tất cả các Result Sets.
     * Chuyển sang public để Controller có thể sử dụng.
     */
    public function callProcedure(string $sql, array $bindings = []): array
    {
        $pdo  = DB::getPdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindings);

        $results = [];
        do {
            // Lấy toàn bộ rows của result set hiện tại
            $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
            $results[] = $rows ?: []; // Đảm bảo luôn là mảng kể cả khi trống
        } while ($stmt->nextRowset());

        return $results;
    }

    /**
     * Gọi Scalar Function (trả về 1 giá trị duy nhất).
     */
    public function callFunction(string $sql, array $bindings = []): mixed
    {
        $row = DB::selectOne($sql, $bindings);
        if (!$row) return null;

        // Ép kiểu object về array và lấy giá trị đầu tiên
        $values = array_values((array) $row);
        return $values[0] ?? null;
    }
}
