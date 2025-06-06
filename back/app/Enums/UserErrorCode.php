<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 使用者管理模組錯誤代碼枚舉
 * 遵循 LomisX3 架構標準的統一錯誤處理機制
 * 
 * @author LomisX3 開發團隊
 * @version V6.2
 */
enum UserErrorCode: string 
{
    case USER_NOT_FOUND = 'USER_NOT_FOUND';
    case USERNAME_EXISTS = 'USERNAME_EXISTS';
    case EMAIL_EXISTS = 'EMAIL_EXISTS';
    case INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    case ACCOUNT_LOCKED = 'ACCOUNT_LOCKED';
    case ACCOUNT_INACTIVE = 'ACCOUNT_INACTIVE';
    case ACCOUNT_PENDING = 'ACCOUNT_PENDING';
    case TWO_FACTOR_REQUIRED = 'TWO_FACTOR_REQUIRED';
    case INVALID_2FA_CODE = 'INVALID_2FA_CODE';
    case TWO_FACTOR_NOT_ENABLED = 'TWO_FACTOR_NOT_ENABLED';
    case WEAK_PASSWORD = 'WEAK_PASSWORD';
    case PASSWORD_RECENTLY_USED = 'PASSWORD_RECENTLY_USED';
    case TOKEN_EXPIRED = 'TOKEN_EXPIRED';
    case TOKEN_NOT_FOUND = 'TOKEN_NOT_FOUND';
    case INSUFFICIENT_PERMISSIONS = 'INSUFFICIENT_PERMISSIONS';
    case STORE_ACCESS_DENIED = 'STORE_ACCESS_DENIED';
    case ROLE_NOT_FOUND = 'ROLE_NOT_FOUND';
    case PERMISSION_NOT_FOUND = 'PERMISSION_NOT_FOUND';
    case CANNOT_DELETE_ADMIN = 'CANNOT_DELETE_ADMIN';
    case AVATAR_UPLOAD_FAILED = 'AVATAR_UPLOAD_FAILED';
    case EMAIL_NOT_VERIFIED = 'EMAIL_NOT_VERIFIED';
    case TOO_MANY_LOGIN_ATTEMPTS = 'TOO_MANY_LOGIN_ATTEMPTS';
    case INVALID_USER_STATUS = 'INVALID_USER_STATUS';
    case ROLE_SYNC_FAILED = 'ROLE_SYNC_FAILED';
    case PERMISSION_SYNC_FAILED = 'PERMISSION_SYNC_FAILED';
    case VALIDATION_FAILED = 'VALIDATION_FAILED';
    case TRANSACTION_FAILED = 'TRANSACTION_FAILED';
    case SYSTEM_ERROR = 'SYSTEM_ERROR';
    case CREATION_FAILED = 'CREATION_FAILED';
    case UPDATE_FAILED = 'UPDATE_FAILED';
    case DELETION_FAILED = 'DELETION_FAILED';
    
    /**
     * 取得錯誤訊息
     */
    public function message(): string
    {
        return match($this) {
            self::USER_NOT_FOUND => '使用者不存在',
            self::USERNAME_EXISTS => '使用者名稱已存在',
            self::EMAIL_EXISTS => '電子郵件已被使用',
            self::INVALID_CREDENTIALS => '帳號或密碼錯誤',
            self::ACCOUNT_LOCKED => '帳號已被鎖定，請聯繫管理員',
            self::ACCOUNT_INACTIVE => '帳號已停用',
            self::ACCOUNT_PENDING => '帳號待審核中',
            self::TWO_FACTOR_REQUIRED => '需要雙因子驗證',
            self::INVALID_2FA_CODE => '雙因子驗證碼錯誤',
            self::TWO_FACTOR_NOT_ENABLED => '尚未啟用雙因子驗證',
            self::WEAK_PASSWORD => '密碼強度不足，需包含大小寫字母、數字及特殊字元',
            self::PASSWORD_RECENTLY_USED => '不能使用最近使用過的密碼',
            self::TOKEN_EXPIRED => 'Token 已過期',
            self::TOKEN_NOT_FOUND => 'Token 不存在',
            self::INSUFFICIENT_PERMISSIONS => '權限不足',
            self::STORE_ACCESS_DENIED => '無權存取此門市資料',
            self::ROLE_NOT_FOUND => '角色不存在',
            self::PERMISSION_NOT_FOUND => '權限不存在',
            self::CANNOT_DELETE_ADMIN => '無法刪除管理員帳號',
            self::AVATAR_UPLOAD_FAILED => '頭像上傳失敗',
            self::EMAIL_NOT_VERIFIED => '電子郵件尚未驗證',
            self::TOO_MANY_LOGIN_ATTEMPTS => '登入嘗試次數過多，請稍後再試',
            self::INVALID_USER_STATUS => '無效的使用者狀態',
            self::ROLE_SYNC_FAILED => '角色同步失敗',
            self::PERMISSION_SYNC_FAILED => '權限同步失敗',
            self::VALIDATION_FAILED => '資料驗證失敗',
            self::TRANSACTION_FAILED => '操作失敗，請稍後重試',
            self::SYSTEM_ERROR => '系統錯誤，請稍後重試',
            self::CREATION_FAILED => '建立失敗，請稍後重試',
            self::UPDATE_FAILED => '更新失敗，請稍後重試',
            self::DELETION_FAILED => '刪除失敗，請稍後重試',
        };
    }
    
    /**
     * 取得 HTTP 狀態碼
     */
    public function httpStatus(): int
    {
        return match($this) {
            self::USER_NOT_FOUND, 
            self::TOKEN_NOT_FOUND, 
            self::ROLE_NOT_FOUND, 
            self::PERMISSION_NOT_FOUND => 404,
            
            self::USERNAME_EXISTS, 
            self::EMAIL_EXISTS, 
            self::PASSWORD_RECENTLY_USED => 409,
            
            self::INVALID_CREDENTIALS, 
            self::WEAK_PASSWORD,
            self::INVALID_2FA_CODE,
            self::TWO_FACTOR_NOT_ENABLED,
            self::EMAIL_NOT_VERIFIED,
            self::INVALID_USER_STATUS => 400,
            
            self::ACCOUNT_LOCKED => 423,
            self::ACCOUNT_INACTIVE => 403,
            self::ACCOUNT_PENDING => 202,
            
            self::TWO_FACTOR_REQUIRED => 428,
            
            self::TOKEN_EXPIRED => 401,
            
            self::TOO_MANY_LOGIN_ATTEMPTS => 429,
            
            self::INSUFFICIENT_PERMISSIONS, 
            self::STORE_ACCESS_DENIED,
            self::CANNOT_DELETE_ADMIN => 403,
            
            self::AVATAR_UPLOAD_FAILED,
            self::VALIDATION_FAILED => 422,
            
            self::ROLE_SYNC_FAILED,
            self::PERMISSION_SYNC_FAILED,
            self::TRANSACTION_FAILED,
            self::SYSTEM_ERROR => 500,
        };
    }

    /**
     * 取得錯誤分類
     */
    public function category(): string
    {
        return match($this) {
            self::USER_NOT_FOUND, 
            self::USERNAME_EXISTS, 
            self::EMAIL_EXISTS => 'user_management',
            
            self::INVALID_CREDENTIALS,
            self::ACCOUNT_LOCKED,
            self::ACCOUNT_INACTIVE,
            self::ACCOUNT_PENDING,
            self::TWO_FACTOR_REQUIRED,
            self::INVALID_2FA_CODE,
            self::TWO_FACTOR_NOT_ENABLED,
            self::TOKEN_EXPIRED,
            self::TOKEN_NOT_FOUND,
            self::TOO_MANY_LOGIN_ATTEMPTS => 'authentication',
            
            self::WEAK_PASSWORD,
            self::PASSWORD_RECENTLY_USED => 'password_security',
            
            self::INSUFFICIENT_PERMISSIONS,
            self::STORE_ACCESS_DENIED,
            self::ROLE_NOT_FOUND,
            self::PERMISSION_NOT_FOUND,
            self::CANNOT_DELETE_ADMIN => 'authorization',
            
            self::AVATAR_UPLOAD_FAILED => 'media',
            
            self::EMAIL_NOT_VERIFIED => 'verification',
            
            self::ROLE_SYNC_FAILED,
            self::PERMISSION_SYNC_FAILED,
            self::VALIDATION_FAILED,
            self::TRANSACTION_FAILED,
            self::INVALID_USER_STATUS,
            self::SYSTEM_ERROR => 'system',
        };
    }
} 