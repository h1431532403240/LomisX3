/**
 * 前端例外處理系統
 * 對應後端 BusinessException 體系
 */

/**
 * 企業級業務異常處理系統
 * 
 * 與後端 PHP BusinessException 保持完全一致
 * 提供型別安全的錯誤處理機制
 */

/**
 * 使用者模組錯誤代碼枚舉
 */
export const UserErrorCode = {
  USER_NOT_FOUND: 'USER_NOT_FOUND',
  USER_ALREADY_EXISTS: 'USER_ALREADY_EXISTS',
  INVALID_CREDENTIALS: 'INVALID_CREDENTIALS',
  EMAIL_ALREADY_TAKEN: 'EMAIL_ALREADY_TAKEN',
  USERNAME_ALREADY_TAKEN: 'USERNAME_ALREADY_TAKEN',
  PASSWORD_TOO_WEAK: 'PASSWORD_TOO_WEAK',
  INVALID_PASSWORD: 'INVALID_PASSWORD',
  ACCOUNT_DISABLED: 'ACCOUNT_DISABLED',
  ACCOUNT_SUSPENDED: 'ACCOUNT_SUSPENDED',
  EMAIL_NOT_VERIFIED: 'EMAIL_NOT_VERIFIED',
  TWO_FACTOR_REQUIRED: 'TWO_FACTOR_REQUIRED',
  TWO_FACTOR_ALREADY_ENABLED: 'TWO_FACTOR_ALREADY_ENABLED',
  TWO_FACTOR_SETUP_FAILED: 'TWO_FACTOR_SETUP_FAILED',
  INVALID_TWO_FACTOR_CODE: 'INVALID_TWO_FACTOR_CODE',
  INVALID_2FA_CODE: 'INVALID_2FA_CODE',
  PERMISSION_DENIED: 'PERMISSION_DENIED',
  STORE_ACCESS_DENIED: 'STORE_ACCESS_DENIED',
  INVALID_ROLE: 'INVALID_ROLE',
  CANNOT_DELETE_SELF: 'CANNOT_DELETE_SELF',
  CANNOT_MODIFY_ADMIN: 'CANNOT_MODIFY_ADMIN',
  BUSINESS_ERROR: 'BUSINESS_ERROR',
} as const;

export type UserErrorCode = (typeof UserErrorCode)[keyof typeof UserErrorCode];

/**
 * 商品分類模組錯誤代碼枚舉
 */
export const ProductCategoryErrorCode = {
  CATEGORY_NOT_FOUND: 'CATEGORY_NOT_FOUND',
  CATEGORY_ALREADY_EXISTS: 'CATEGORY_ALREADY_EXISTS',
  SLUG_ALREADY_TAKEN: 'SLUG_ALREADY_TAKEN',
  INVALID_PARENT_CATEGORY: 'INVALID_PARENT_CATEGORY',
  CIRCULAR_REFERENCE: 'CIRCULAR_REFERENCE',
  CATEGORY_HAS_CHILDREN: 'CATEGORY_HAS_CHILDREN',
  CATEGORY_HAS_PRODUCTS: 'CATEGORY_HAS_PRODUCTS',
  MAX_DEPTH_EXCEEDED: 'MAX_DEPTH_EXCEEDED',
  POSITION_CONFLICT: 'POSITION_CONFLICT',
  CATEGORY_BUSINESS_ERROR: 'CATEGORY_BUSINESS_ERROR',
} as const;

export type ProductCategoryErrorCode = (typeof ProductCategoryErrorCode)[keyof typeof ProductCategoryErrorCode];

/**
 * 通用錯誤代碼枚舉
 */
export const GeneralErrorCode = {
  VALIDATION_ERROR: 'VALIDATION_ERROR',
  AUTHENTICATION_ERROR: 'AUTHENTICATION_ERROR',
  AUTHORIZATION_ERROR: 'AUTHORIZATION_ERROR',
  NOT_FOUND_ERROR: 'NOT_FOUND_ERROR',
  NETWORK_ERROR: 'NETWORK_ERROR',
  SERVER_ERROR: 'SERVER_ERROR',
  GENERAL_BUSINESS_ERROR: 'GENERAL_BUSINESS_ERROR',
  UNKNOWN_ERROR: 'UNKNOWN_ERROR',
} as const;

export type GeneralErrorCode = (typeof GeneralErrorCode)[keyof typeof GeneralErrorCode];

/**
 * 所有錯誤代碼的聯合型別
 */
export type ErrorCode = UserErrorCode | ProductCategoryErrorCode | GeneralErrorCode;

/**
 * 錯誤詳細資訊介面
 */
export interface ErrorDetails {
  code: ErrorCode;
  message: string;
  field?: string;
  value?: any;
  context?: Record<string, any>;
}

/**
 * 業務異常類別
 * 
 * 與後端 PHP BusinessException 功能對等
 * 提供完整的錯誤處理和國際化支援
 */
export class BusinessException extends Error {
  /**
   * 錯誤代碼
   */
  public readonly code: ErrorCode;

  /**
   * HTTP 狀態碼
   */
  public readonly statusCode: number;

  /**
   * 錯誤詳細資訊
   */
  public readonly details: ErrorDetails[];

  /**
   * 時間戳記
   */
  public readonly timestamp: string;

  /**
   * 建構函數
   */
  constructor(
    message: string,
    code: ErrorCode = GeneralErrorCode.GENERAL_BUSINESS_ERROR,
    statusCode: number = 400,
    details: ErrorDetails[] = []
  ) {
    super(message);
    
    this.name = 'BusinessException';
    this.code = code;
    this.statusCode = statusCode;
    this.details = details;
    this.timestamp = new Date().toISOString();

    // 確保正確的原型鏈
    Object.setPrototypeOf(this, BusinessException.prototype);
  }

  /**
   * 從錯誤代碼建立業務異常
   */
  static fromErrorCode(
    code: ErrorCode, 
    customMessage?: string,
    statusCode?: number
  ): BusinessException {
    const defaultMessage = BusinessException.getDefaultMessage(code);
    const defaultStatusCode = BusinessException.getDefaultStatusCode(code);
    
    return new BusinessException(
      customMessage || defaultMessage,
      code,
      statusCode || defaultStatusCode
    );
  }

  /**
   * 從 API 錯誤回應建立業務異常
   */
  static fromApiResponse(response: {
    message?: string;
    code?: string;
    errors?: any;
    status?: number;
  }): BusinessException {
    const code = BusinessException.parseErrorCode(response.code);
    const message = response.message || '發生未知錯誤';
    const statusCode = response.status || 500;
    
    // 轉換驗證錯誤為詳細資訊
    const details: ErrorDetails[] = [];
    if (response.errors && typeof response.errors === 'object') {
      Object.entries(response.errors).forEach(([field, messages]) => {
        if (Array.isArray(messages)) {
          messages.forEach((msg: string) => {
            details.push({
              code: GeneralErrorCode.VALIDATION_ERROR,
              message: msg,
              field,
            });
          });
        } else if (typeof messages === 'string') {
          details.push({
            code: GeneralErrorCode.VALIDATION_ERROR,
            message: messages,
            field,
          });
        }
      });
    }
    
    return new BusinessException(message, code, statusCode, details);
  }

  /**
   * 驗證錯誤
   */
  static validationError(
    field: string, 
    message: string, 
    value?: any
  ): BusinessException {
    const details: ErrorDetails[] = [{
      code: GeneralErrorCode.VALIDATION_ERROR,
      message,
      field,
      value,
    }];
    
    return new BusinessException(
      `驗證錯誤：${field} - ${message}`,
      GeneralErrorCode.VALIDATION_ERROR,
      422,
      details
    );
  }

  /**
   * 權限錯誤
   */
  static permissionDenied(action: string, resource?: string): BusinessException {
    const message = resource 
      ? `無權限執行 ${action} 操作於 ${resource}`
      : `無權限執行 ${action} 操作`;
      
    return new BusinessException(
      message,
      UserErrorCode.PERMISSION_DENIED,
      403
    );
  }

  /**
   * 網路錯誤
   */
  static networkError(message: string = '網路連線錯誤'): BusinessException {
    return new BusinessException(
      message,
      GeneralErrorCode.NETWORK_ERROR,
      0 // 網路錯誤通常沒有 HTTP 狀態碼
    );
  }

  /**
   * 取得錯誤代碼的預設訊息
   */
  private static getDefaultMessage(code: ErrorCode): string {
    // 使用 switch 語句避免 TypeScript 複雜性
    switch (code) {
      // 使用者錯誤
      case UserErrorCode.USER_NOT_FOUND:
        return '找不到指定的使用者';
      case UserErrorCode.USER_ALREADY_EXISTS:
        return '使用者已存在';
      case UserErrorCode.INVALID_CREDENTIALS:
        return '登入憑證無效';
      case UserErrorCode.EMAIL_ALREADY_TAKEN:
        return '電子郵件已被使用';
      case UserErrorCode.USERNAME_ALREADY_TAKEN:
        return '使用者名稱已被使用';
      case UserErrorCode.PASSWORD_TOO_WEAK:
        return '密碼強度不足';
      case UserErrorCode.ACCOUNT_DISABLED:
        return '帳號已停用';
      case UserErrorCode.ACCOUNT_SUSPENDED:
        return '帳號已暫停';
      case UserErrorCode.EMAIL_NOT_VERIFIED:
        return '電子郵件尚未驗證';
      case UserErrorCode.TWO_FACTOR_REQUIRED:
        return '需要雙因子驗證';
      case UserErrorCode.INVALID_TWO_FACTOR_CODE:
        return '雙因子驗證碼無效';
      case UserErrorCode.PERMISSION_DENIED:
        return '權限不足';
      case UserErrorCode.STORE_ACCESS_DENIED:
        return '無權存取此門市';
      case UserErrorCode.INVALID_ROLE:
        return '無效的角色';
      case UserErrorCode.CANNOT_DELETE_SELF:
        return '無法刪除自己的帳號';
      case UserErrorCode.CANNOT_MODIFY_ADMIN:
        return '無法修改管理員帳號';
      case UserErrorCode.BUSINESS_ERROR:
        return '業務邏輯錯誤';

      // 商品分類錯誤
      case ProductCategoryErrorCode.CATEGORY_NOT_FOUND:
        return '找不到指定的商品分類';
      case ProductCategoryErrorCode.CATEGORY_ALREADY_EXISTS:
        return '商品分類已存在';
      case ProductCategoryErrorCode.SLUG_ALREADY_TAKEN:
        return '分類代碼已被使用';
      case ProductCategoryErrorCode.INVALID_PARENT_CATEGORY:
        return '無效的父分類';
      case ProductCategoryErrorCode.CIRCULAR_REFERENCE:
        return '檢測到循環引用';
      case ProductCategoryErrorCode.CATEGORY_HAS_CHILDREN:
        return '分類下還有子分類';
      case ProductCategoryErrorCode.CATEGORY_HAS_PRODUCTS:
        return '分類下還有商品';
      case ProductCategoryErrorCode.MAX_DEPTH_EXCEEDED:
        return '超過最大層級深度';
      case ProductCategoryErrorCode.POSITION_CONFLICT:
        return '位置衝突';
      case ProductCategoryErrorCode.CATEGORY_BUSINESS_ERROR:
        return '分類業務邏輯錯誤';

      // 通用錯誤
      case GeneralErrorCode.VALIDATION_ERROR:
        return '資料驗證錯誤';
      case GeneralErrorCode.AUTHORIZATION_ERROR:
        return '授權錯誤';
      case GeneralErrorCode.NETWORK_ERROR:
        return '網路連線錯誤';
      case GeneralErrorCode.SERVER_ERROR:
        return '伺服器錯誤';
      case GeneralErrorCode.GENERAL_BUSINESS_ERROR:
        return '業務邏輯錯誤';
      case GeneralErrorCode.UNKNOWN_ERROR:
        return '未知錯誤';

      default:
        return '未知錯誤';
    }
  }

  /**
   * 取得錯誤代碼的預設 HTTP 狀態碼
   */
  private static getDefaultStatusCode(code: ErrorCode): number {
    switch (code) {
      // 權限相關錯誤 - 403
      case UserErrorCode.PERMISSION_DENIED:
      case UserErrorCode.STORE_ACCESS_DENIED:
      case GeneralErrorCode.AUTHORIZATION_ERROR:
        return 403;

      // 找不到資源錯誤 - 404
      case UserErrorCode.USER_NOT_FOUND:
      case ProductCategoryErrorCode.CATEGORY_NOT_FOUND:
        return 404;

      // 驗證錯誤 - 422
      case GeneralErrorCode.VALIDATION_ERROR:
      case UserErrorCode.PASSWORD_TOO_WEAK:
      case UserErrorCode.INVALID_TWO_FACTOR_CODE:
        return 422;

      // 認證錯誤 - 401
      case UserErrorCode.INVALID_CREDENTIALS:
      case UserErrorCode.EMAIL_NOT_VERIFIED:
      case UserErrorCode.TWO_FACTOR_REQUIRED:
        return 401;

      // 衝突錯誤 - 409
      case UserErrorCode.USER_ALREADY_EXISTS:
      case UserErrorCode.EMAIL_ALREADY_TAKEN:
      case UserErrorCode.USERNAME_ALREADY_TAKEN:
      case ProductCategoryErrorCode.CATEGORY_ALREADY_EXISTS:
      case ProductCategoryErrorCode.SLUG_ALREADY_TAKEN:
        return 409;

      // 網路錯誤 - 0
      case GeneralErrorCode.NETWORK_ERROR:
        return 0;

      // 預設為 400 (Bad Request)
      default:
        return 400;
    }
  }

  /**
   * 解析錯誤代碼字串
   */
  private static parseErrorCode(codeString?: string): ErrorCode {
    if (!codeString) {
      return GeneralErrorCode.UNKNOWN_ERROR;
    }

    // 檢查是否為已知的錯誤代碼
    const allErrorCodes = [
      ...Object.values(UserErrorCode),
      ...Object.values(ProductCategoryErrorCode),
      ...Object.values(GeneralErrorCode),
    ];

    if (allErrorCodes.includes(codeString as ErrorCode)) {
      return codeString as ErrorCode;
    }

    return GeneralErrorCode.UNKNOWN_ERROR;
  }

  /**
   * 轉換為 JSON 格式
   */
  public toJSON(): object {
    return {
      name: this.name,
      message: this.message,
      code: this.code,
      statusCode: this.statusCode,
      details: this.details,
      timestamp: this.timestamp,
      stack: this.stack,
    };
  }

  /**
   * 是否為特定錯誤代碼
   */
  public isCode(code: ErrorCode): boolean {
    return this.code === code;
  }

  /**
   * 是否為驗證錯誤
   */
  public isValidationError(): boolean {
    return this.code === GeneralErrorCode.VALIDATION_ERROR;
  }

  /**
   * 是否為權限錯誤
   */
  public isPermissionError(): boolean {
    const permissionErrorCodes: ErrorCode[] = [
      UserErrorCode.PERMISSION_DENIED,
      UserErrorCode.STORE_ACCESS_DENIED,
      GeneralErrorCode.AUTHORIZATION_ERROR,
    ];
    return permissionErrorCodes.includes(this.code);
  }

  /**
   * 是否為網路錯誤
   */
  public isNetworkError(): boolean {
    return this.code === GeneralErrorCode.NETWORK_ERROR;
  }
}

/**
 * 驗證例外
 */
export class ValidationException extends BusinessException {
  public errors: Record<string, string[]>;

  constructor(
    message: string, 
    errors: Record<string, string[]> = {},
    errorCode: ErrorCode = GeneralErrorCode.VALIDATION_ERROR
  ) {
    super(message, errorCode, 422);
    this.name = 'ValidationException';
    this.errors = errors;
  }

  getErrors(): Record<string, string[]> {
    return this.errors;
  }

  getFieldErrors(field: string): string[] {
    return this.errors[field] || [];
  }

  hasFieldError(field: string): boolean {
    return Object.prototype.hasOwnProperty.call(this.errors, field);
  }
}

/**
 * 認證例外
 */
export class AuthenticationException extends BusinessException {
  constructor(message: string = '認證失敗', errorCode: ErrorCode = GeneralErrorCode.AUTHENTICATION_ERROR) {
    super(message, errorCode, 401);
    this.name = 'AuthenticationException';
  }
}

/**
 * 授權例外
 */
export class AuthorizationException extends BusinessException {
  constructor(message: string = '沒有權限執行此操作', errorCode: ErrorCode = GeneralErrorCode.AUTHORIZATION_ERROR) {
    super(message, errorCode, 403);
    this.name = 'AuthorizationException';
  }
}

/**
 * 資源未找到例外
 */
export class NotFoundException extends BusinessException {
  constructor(message: string = '資源未找到', errorCode: ErrorCode = GeneralErrorCode.NOT_FOUND_ERROR) {
    super(message, errorCode, 404);
    this.name = 'NotFoundException';
  }
}

/**
 * 伺服器錯誤例外
 */
export class ServerException extends BusinessException {
  constructor(message: string = '伺服器內部錯誤', errorCode: ErrorCode = GeneralErrorCode.SERVER_ERROR) {
    super(message, errorCode, 500);
    this.name = 'ServerException';
  }
}

/**
 * 從 API 回應創建對應的例外
 */
export function createExceptionFromApiError(error: any): BusinessException {
  const message = error.message || '未知錯誤';
  const code = error.error_code || error.code || 'UNKNOWN_ERROR';
  const status = error.status || error.http_status || 500;
  
  switch (status) {
    case 400:
      return new BusinessException(message, code, status, error.details);
    case 401:
      return new AuthenticationException(message, code);
    case 403:
      return new AuthorizationException(message, code);
    case 404:
      return new NotFoundException(message, code);
    case 422:
      return new ValidationException(message, error.errors || {}, code);
    case 500:
    default:
      return new ServerException(message, code);
  }
}

// 向後相容性匯出
export type UserErrorCodeType = UserErrorCode; 