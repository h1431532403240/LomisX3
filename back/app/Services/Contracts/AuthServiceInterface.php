<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * 認證服務介面
 * 定義認證相關功能的契約
 */
interface AuthServiceInterface
{
    /**
     * 使用者登入
     * 
     * @param array $credentials
     * @return array
     */
    public function login(array $credentials): array;

    /**
     * 驗證使用者狀態
     * 
     * @param User $user
     * @return void
     */
    public function validateUserStatus(User $user): void;

    /**
     * 更新登入資訊
     * 
     * @param User $user
     * @param Request $request
     * @return void
     */
    public function updateLoginInfo(User $user, Request $request): void;

    /**
     * 驗證密碼
     * 
     * @param User $user
     * @param string $password
     * @return bool
     */
    public function verifyPassword(User $user, string $password): bool;

    /**
     * 處理登入失敗
     * 
     * @param User $user
     * @return void
     */
    public function handleLoginFailure(User $user): void;

    /**
     * 重設登入失敗次數
     * 
     * @param User $user
     * @return void
     */
    public function resetLoginAttempts(User $user): void;

    /**
     * 檢查帳號是否被鎖定
     * 
     * @param User $user
     * @return bool
     */
    public function isAccountLocked(User $user): bool;

    /**
     * 解鎖帳號
     * 
     * @param User $user
     * @return void
     */
    public function unlockAccount(User $user): void;

    /**
     * 取得登入歷史統計
     * 
     * @param int $days
     * @return array
     */
    public function getLoginStatistics(int $days = 30): array;
} 