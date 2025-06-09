<?php

/**
 * LomisX3 用戶刪除狀態診斷工具
 * 
 * 使用方法：php database-check.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Facade;

// 設定資料庫連線
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'database'  => 'lomis_x3',  // 請根據您的資料庫名稱調整
    'username'  => 'root',      // 請根據您的資料庫使用者調整
    'password'  => '',          // 請根據您的資料庫密碼調整
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "🔍 LomisX3 用戶刪除狀態診斷\n";
echo "================================\n\n";

try {
    // 檢查所有用戶的刪除狀態
    $users = $capsule->table('users')
        ->select('id', 'username', 'email', 'deleted_at', 'created_at', 'updated_at')
        ->orderBy('id')
        ->get();
    
    echo "📊 所有用戶狀態：\n";
    foreach ($users as $user) {
        $status = $user->deleted_at ? '❌ 已刪除' : '✅ 正常';
        echo sprintf(
            "ID: %d | %s | %s | %s | 刪除時間: %s\n",
            $user->id,
            str_pad($user->username ?? 'N/A', 15),
            str_pad($user->email ?? 'N/A', 25),
            $status,
            $user->deleted_at ?? 'NULL'
        );
    }
    
    echo "\n🔍 軟刪除用戶詳情：\n";
    $deletedUsers = $capsule->table('users')
        ->whereNotNull('deleted_at')
        ->get();
    
    if ($deletedUsers->isEmpty()) {
        echo "✅ 沒有找到軟刪除的用戶\n";
    } else {
        foreach ($deletedUsers as $user) {
            echo sprintf(
                "🗑️ 用戶 ID %d (%s) 在 %s 被軟刪除\n",
                $user->id,
                $user->username,
                $user->deleted_at
            );
        }
    }
    
    // 檢查特定用戶 ID
    $targetIds = [14, 15];
    echo "\n🎯 特定用戶檢查：\n";
    foreach ($targetIds as $id) {
        $user = $capsule->table('users')
            ->where('id', $id)
            ->first();
        
        if ($user) {
            $status = $user->deleted_at ? "❌ 已於 {$user->deleted_at} 被軟刪除" : "✅ 正常";
            echo "用戶 ID {$id}: {$status}\n";
        } else {
            echo "用戶 ID {$id}: ❓ 不存在\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ 資料庫連線錯誤: " . $e->getMessage() . "\n";
    echo "請檢查資料庫連線參數\n";
}

echo "\n✅ 診斷完成\n"; 