/**
 * 🏷️ 狀態標籤組件
 * 基於 shadcn/ui Badge 封裝的可重用狀態標籤
 * 提供統一的狀態顯示樣式和行為
 */

// React import removed for production build
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { 
  CheckCircle, 
  XCircle, 
  Clock, 
  AlertTriangle, 
  Info,
  Loader2,
  Eye,
  EyeOff,
} from 'lucide-react';

/**
 * 🎨 狀態類型定義
 */
export type StatusType = 
  | 'active' 
  | 'inactive' 
  | 'pending' 
  | 'warning' 
  | 'error' 
  | 'info' 
  | 'loading'
  | 'visible'
  | 'hidden';

/**
 * 🔧 StatusBadge Props 介面
 */
interface StatusBadgeProps {
  /** 狀態類型 */
  status: StatusType;
  /** 自訂文字，不提供則使用預設文字 */
  text?: string;
  /** 是否顯示圖示 */
  showIcon?: boolean;
  /** 額外的 CSS 類名 */
  className?: string;
  /** 尺寸變體 */
  size?: 'sm' | 'default' | 'lg';
  /** 點擊事件 */
  onClick?: () => void;
  /** 是否可點擊 */
  clickable?: boolean;
}

/**
 * 🎭 取得狀態對應的配置
 */
const getStatusConfig = (status: StatusType) => {
  const configs = {
    active: {
      text: '啟用',
      icon: CheckCircle,
      variant: 'default' as const,
      className: 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800',
    },
    inactive: {
      text: '停用',
      icon: XCircle,
      variant: 'secondary' as const,
      className: 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800',
    },
    pending: {
      text: '待處理',
      icon: Clock,
      variant: 'outline' as const,
      className: 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400 dark:border-yellow-800',
    },
    warning: {
      text: '警告',
      icon: AlertTriangle,
      variant: 'outline' as const,
      className: 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-900/20 dark:text-orange-400 dark:border-orange-800',
    },
    error: {
      text: '錯誤',
      icon: XCircle,
      variant: 'destructive' as const,
      className: 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800',
    },
    info: {
      text: '資訊',
      icon: Info,
      variant: 'outline' as const,
      className: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800',
    },
    loading: {
      text: '載入中',
      icon: Loader2,
      variant: 'outline' as const,
      className: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800',
    },
    visible: {
      text: '顯示',
      icon: Eye,
      variant: 'default' as const,
      className: 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800',
    },
    hidden: {
      text: '隱藏',
      icon: EyeOff,
      variant: 'secondary' as const,
      className: 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-800',
    },
  };

  return configs[status];
};

/**
 * 🎨 取得尺寸對應的樣式
 */
const getSizeStyles = (size: 'sm' | 'default' | 'lg') => {
  switch (size) {
    case 'sm':
      return {
        badge: 'text-xs px-2 py-0.5 h-5',
        icon: 'h-3 w-3',
      };
    case 'lg':
      return {
        badge: 'text-sm px-3 py-1 h-7',
        icon: 'h-4 w-4',
      };
    default:
      return {
        badge: 'text-xs px-2.5 py-0.5 h-6',
        icon: 'h-3.5 w-3.5',
      };
  }
};

/**
 * 🏷️ 狀態標籤組件
 */
export function StatusBadge({
  status,
  text,
  showIcon = true,
  className,
  size = 'default',
  onClick,
  clickable = false,
}: StatusBadgeProps) {
  const config = getStatusConfig(status);
  const sizeStyles = getSizeStyles(size);
  const Icon = config.icon;

  const isClickable = clickable || !!onClick;

  return (
    <Badge
      variant={config.variant}
      className={cn(
        'inline-flex items-center gap-1.5 font-medium border',
        config.className,
        sizeStyles.badge,
        isClickable && 'cursor-pointer hover:opacity-80 transition-opacity',
        className
      )}
      onClick={onClick}
    >
      {showIcon && (
        <Icon 
          className={cn(
            sizeStyles.icon,
            status === 'loading' && 'animate-spin'
          )} 
        />
      )}
      <span>{text ?? config.text}</span>
    </Badge>
  );
}

/**
 * 🔄 布林值狀態標籤
 * 根據布林值自動顯示啟用/停用狀態
 */
interface BooleanStatusBadgeProps extends Omit<StatusBadgeProps, 'status'> {
  /** 布林值狀態 */
  value: boolean;
  /** 啟用時的文字 */
  activeText?: string;
  /** 停用時的文字 */
  inactiveText?: string;
}

export function BooleanStatusBadge({
  value,
  activeText,
  inactiveText,
  ...props
}: BooleanStatusBadgeProps) {
  return (
    <StatusBadge
      status={value ? 'active' : 'inactive'}
      text={value ? activeText : inactiveText}
      {...props}
    />
  );
}

/**
 * 🔢 數字狀態標籤
 * 顯示數字並根據數值範圍自動選擇狀態
 */
interface NumberStatusBadgeProps extends Omit<StatusBadgeProps, 'status' | 'text'> {
  /** 數字值 */
  value: number;
  /** 警告閾值 */
  warningThreshold?: number;
  /** 錯誤閾值 */
  errorThreshold?: number;
  /** 數字格式化函數 */
  formatter?: (value: number) => string;
}

export function NumberStatusBadge({
  value,
  warningThreshold,
  errorThreshold,
  formatter = (n) => n.toString(),
  ...props
}: NumberStatusBadgeProps) {
  let status: StatusType = 'info';
  
  if (errorThreshold !== undefined && value >= errorThreshold) {
    status = 'error';
  } else if (warningThreshold !== undefined && value >= warningThreshold) {
    status = 'warning';
  } else if (value > 0) {
    status = 'active';
  }

  return (
    <StatusBadge
      status={status}
      text={formatter(value)}
      showIcon={false}
      {...props}
    />
  );
}

/**
 * 🎯 狀態切換標籤
 * 可點擊切換狀態的標籤
 */
interface ToggleStatusBadgeProps extends Omit<StatusBadgeProps, 'status' | 'onClick'> {
  /** 當前狀態 */
  active: boolean;
  /** 狀態切換事件 */
  onToggle: (active: boolean) => void;
  /** 是否禁用 */
  disabled?: boolean;
  /** 載入狀態 */
  loading?: boolean;
}

export function ToggleStatusBadge({
  active,
  onToggle,
  disabled = false,
  loading = false,
  ...props
}: ToggleStatusBadgeProps) {
  const handleClick = () => {
    if (!disabled && !loading) {
      onToggle(!active);
    }
  };

  if (loading) {
    return <StatusBadge status="loading" {...props} />;
  }

  return (
    <StatusBadge
      status={active ? 'active' : 'inactive'}
      onClick={handleClick}
      clickable={!disabled}
      className={cn(
        disabled && 'opacity-50 cursor-not-allowed',
        props.className
      )}
      {...props}
    />
  );
} 