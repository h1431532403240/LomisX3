/**
 * 分層錯誤處理策略
 * 提供細粒度的錯誤分類和對應的 UX 處理
 */
import { toast } from 'sonner';
import type { ApiError } from './openapi-client';

// 🆕 錯誤類型分類
export const ErrorCategory = {
  AUTHENTICATION: 'authentication',
  AUTHORIZATION: 'authorization', 
  VALIDATION: 'validation',
  NETWORK: 'network',
  SERVER: 'server',
  BUSINESS: 'business',
  UNKNOWN: 'unknown'
} as const;

export type ErrorCategory = (typeof ErrorCategory)[keyof typeof ErrorCategory];

// 🆕 錯誤嚴重程度
export const ErrorSeverity = {
  LOW: 'low',       // 不影響操作，僅提示
  MEDIUM: 'medium', // 影響當前操作
  HIGH: 'high',     // 影響整個功能
  CRITICAL: 'critical' // 影響整個應用
} as const;

export type ErrorSeverity = (typeof ErrorSeverity)[keyof typeof ErrorSeverity];

// 🆕 錯誤處理選項
export interface ErrorHandlingOptions {
  /** 是否顯示 toast 通知 */
  showToast?: boolean;
  /** 自訂錯誤訊息 */
  customMessage?: string;
  /** 是否記錄到控制台 */
  logToConsole?: boolean;
  /** 額外的處理函數 */
  onError?: (error: ApiError, category: ErrorCategory) => void;
  /** 是否自動重試 */
  enableRetry?: boolean;
}

// 🆕 錯誤分類器
export function categorizeError(error: ApiError): ErrorCategory {
  if (!error.status) return ErrorCategory.UNKNOWN;
  
  switch (error.status) {
    case 401:
      return ErrorCategory.AUTHENTICATION;
    case 403:
      return ErrorCategory.AUTHORIZATION;
    case 422:
    case 400:
      return ErrorCategory.VALIDATION;
    case 404:
      return ErrorCategory.BUSINESS;
    case 500:
    case 502:
    case 503:
    case 504:
      return ErrorCategory.SERVER;
    default:
      if (error.status >= 400 && error.status < 500) {
        return ErrorCategory.BUSINESS;
      } else if (error.status >= 500) {
        return ErrorCategory.SERVER;
      }
      return ErrorCategory.UNKNOWN;
  }
}

// 🆕 錯誤嚴重程度評估
export function assessErrorSeverity(category: ErrorCategory): ErrorSeverity {
  switch (category) {
    case ErrorCategory.AUTHENTICATION:
      return ErrorSeverity.HIGH;
    case ErrorCategory.AUTHORIZATION:
      return ErrorSeverity.MEDIUM;
    case ErrorCategory.VALIDATION:
      return ErrorSeverity.LOW;
    case ErrorCategory.NETWORK:
      return ErrorSeverity.MEDIUM;
    case ErrorCategory.SERVER:
      return ErrorSeverity.HIGH;
    case ErrorCategory.BUSINESS:
      return ErrorSeverity.MEDIUM;
    default:
      return ErrorSeverity.MEDIUM;
  }
}

// 🆕 認證錯誤處理器
export const authErrorHandler = {
  handle: (error: ApiError, options: ErrorHandlingOptions = {}) => {
    const { showToast = true, customMessage, logToConsole = true } = options;
    
    if (logToConsole) {
      console.warn('Authentication Error:', error);
    }
    
    if (showToast) {
      toast.error(customMessage || '請重新登入', {
        description: '您的登入狀態已過期，請重新驗證身份',
        action: {
          label: '前往登入',
          onClick: () => {
            // TODO: 實作登入頁面導向
            console.log('Navigate to login page');
          },
        },
      });
    }
    
    options.onError?.(error, ErrorCategory.AUTHENTICATION);
  },
};

// 🆕 授權錯誤處理器
export const authorizationErrorHandler = {
  handle: (error: ApiError, options: ErrorHandlingOptions = {}) => {
    const { showToast = true, customMessage, logToConsole = true } = options;
    
    if (logToConsole) {
      console.warn('Authorization Error:', error);
    }
    
    if (showToast) {
      toast.error(customMessage || '權限不足', {
        description: '您沒有執行此操作的權限，請聯絡管理員',
      });
    }
    
    options.onError?.(error, ErrorCategory.AUTHORIZATION);
  },
};

// 🆕 驗證錯誤處理器
export const validationErrorHandler = {
  handle: (error: ApiError, options: ErrorHandlingOptions = {}) => {
    const { showToast = true, customMessage, logToConsole = true } = options;
    
    if (logToConsole) {
      console.info('Validation Error:', error);
    }
    
    if (showToast) {
      const firstError = error.errors ? Object.values(error.errors)[0]?.[0] : undefined;
      const message = customMessage || firstError || error.message || '輸入資料驗證失敗';
      
      toast.error('輸入驗證失敗', {
        description: message,
      });
    }
    
    options.onError?.(error, ErrorCategory.VALIDATION);
  },
};

// 🆕 網路錯誤處理器
export const networkErrorHandler = {
  handle: (error: ApiError, options: ErrorHandlingOptions = {}) => {
    const { showToast = true, customMessage, logToConsole = true, enableRetry = false } = options;
    
    if (logToConsole) {
      console.error('Network Error:', error);
    }
    
    if (showToast) {
      toast.error(customMessage || '網路連線異常', {
        description: '請檢查網路連線狀態並稍後再試',
        action: enableRetry ? {
          label: '重試',
          onClick: () => {
            // TODO: 實作重試邏輯
            console.log('Retry network request');
          },
        } : undefined,
      });
    }
    
    options.onError?.(error, ErrorCategory.NETWORK);
  },
};

// 🆕 伺服器錯誤處理器
export const serverErrorHandler = {
  handle: (error: ApiError, options: ErrorHandlingOptions = {}) => {
    const { showToast = true, customMessage, logToConsole = true } = options;
    
    if (logToConsole) {
      console.error('Server Error:', error);
    }
    
    if (showToast) {
      toast.error(customMessage || '伺服器暫時無法回應', {
        description: '伺服器發生錯誤，請稍後再試或聯絡技術支援',
      });
    }
    
    options.onError?.(error, ErrorCategory.SERVER);
  },
};

// 🆕 業務邏輯錯誤處理器
export const businessErrorHandler = {
  handle: (error: ApiError, options: ErrorHandlingOptions = {}) => {
    const { showToast = true, customMessage, logToConsole = true } = options;
    
    if (logToConsole) {
      console.warn('Business Logic Error:', error);
    }
    
    if (showToast) {
      toast.warning(customMessage || error.message || '操作無法完成', {
        description: '請檢查操作條件是否滿足要求',
      });
    }
    
    options.onError?.(error, ErrorCategory.BUSINESS);
  },
};

// 🆕 通用錯誤處理器
export const unknownErrorHandler = {
  handle: (error: ApiError, options: ErrorHandlingOptions = {}) => {
    const { showToast = true, customMessage, logToConsole = true } = options;
    
    if (logToConsole) {
      console.error('Unknown Error:', error);
    }
    
    if (showToast) {
      toast.error(customMessage || '發生未知錯誤', {
        description: '請稍後再試，如問題持續請聯絡技術支援',
      });
    }
    
    options.onError?.(error, ErrorCategory.UNKNOWN);
  },
};

// 🆕 錯誤處理器映射
const errorHandlers = {
  [ErrorCategory.AUTHENTICATION]: authErrorHandler,
  [ErrorCategory.AUTHORIZATION]: authorizationErrorHandler,
  [ErrorCategory.VALIDATION]: validationErrorHandler,
  [ErrorCategory.NETWORK]: networkErrorHandler,
  [ErrorCategory.SERVER]: serverErrorHandler,
  [ErrorCategory.BUSINESS]: businessErrorHandler,
  [ErrorCategory.UNKNOWN]: unknownErrorHandler,
} as const;

// 🆕 智能錯誤處理主函數
export function handleError(error: ApiError, options: ErrorHandlingOptions = {}) {
  const category = categorizeError(error);
  const severity = assessErrorSeverity(category);
  const handler = errorHandlers[category];
  
  // 根據嚴重程度調整選項
  const adjustedOptions: ErrorHandlingOptions = {
    ...options,
    logToConsole: options.logToConsole ?? (severity !== ErrorSeverity.LOW),
  };
  
  handler.handle(error, adjustedOptions);
  
  return { category, severity };
}

// 🆕 Hook 專用的錯誤處理器
export const hookErrorHandlers = {
  /** 查詢錯誤 - 通常不顯示 toast，由組件決定如何展示 */
  query: (error: ApiError, options: Omit<ErrorHandlingOptions, 'showToast'> = {}) => {
    return handleError(error, { ...options, showToast: false });
  },
  
  /** 變更錯誤 - 顯示 toast 通知 */
  mutation: (error: ApiError, options: ErrorHandlingOptions = {}) => {
    return handleError(error, { showToast: true, ...options });
  },
  
  /** 背景同步錯誤 - 靜默處理 */
  background: (error: ApiError, options: Omit<ErrorHandlingOptions, 'showToast' | 'logToConsole'> = {}) => {
    return handleError(error, { ...options, showToast: false, logToConsole: false });
  },
};

// 🆕 預設匯出
export default {
  handleError,
  categorizeError,
  assessErrorSeverity,
  hookErrorHandlers,
  ErrorCategory,
  ErrorSeverity,
}; 