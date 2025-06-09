import { cn } from '@/lib/utils';
import { Loader2 } from 'lucide-react';

/**
 * 載入指示器組件
 * 提供統一的載入狀態視覺反饋
 */
interface LoadingSpinnerProps {
  /** 尺寸大小 */
  size?: 'sm' | 'md' | 'lg' | 'xl';
  /** 自定義 CSS 類別 */
  className?: string;
  /** 顯示文字 */
  text?: string;
  /** 是否顯示為覆蓋層 */
  overlay?: boolean;
}

/**
 * 尺寸映射
 */
const sizeClasses = {
  sm: 'h-4 w-4',
  md: 'h-6 w-6',
  lg: 'h-8 w-8',
  xl: 'h-12 w-12',
};

/**
 * LoadingSpinner 組件
 * 使用 Lucide React 的 Loader2 圖標，支援多種尺寸和自定義樣式
 */
export const LoadingSpinner: React.FC<LoadingSpinnerProps> = ({
  size = 'md',
  className,
  text,
  overlay = false,
}) => {
  const spinnerContent = (
    <div className={cn(
      'flex flex-col items-center justify-center gap-2',
      className
    )}>
      <Loader2 
        className={cn(
          'animate-spin text-primary',
          sizeClasses[size]
        )}
      />
      {text && (
        <p className="text-sm text-muted-foreground animate-pulse">
          {text}
        </p>
      )}
    </div>
  );

  if (overlay) {
    return (
      <div className="fixed inset-0 bg-background/80 backdrop-blur-sm z-50 flex items-center justify-center">
        {spinnerContent}
      </div>
    );
  }

  return spinnerContent;
};

/**
 * 頁面載入指示器
 * 專門用於頁面級別的載入狀態
 */
export const PageLoadingSpinner: React.FC<{
  text?: string;
}> = ({ text = '載入中...' }) => {
  return (
    <div className="flex items-center justify-center min-h-screen">
      <LoadingSpinner size="lg" text={text} />
    </div>
  );
};

/**
 * 按鈕載入指示器
 * 專門用於按鈕內的載入狀態
 */
export const ButtonLoadingSpinner: React.FC<{
  className?: string;
}> = ({ className }) => {
  return (
    <Loader2 
      className={cn(
        'h-4 w-4 animate-spin',
        className
      )}
    />
  );
}; 