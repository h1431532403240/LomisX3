import { cn } from "@/lib/utils"

/**
 * Skeleton 基礎組件 Props
 */
interface SkeletonProps extends React.HTMLAttributes<HTMLDivElement> {
  /**
   * 是否顯示動畫效果
   * @default true
   */
  animate?: boolean;
}

/**
 * Skeleton 基礎組件
 * 用於顯示載入狀態的骨架屏
 */
function Skeleton({
  className,
  animate = true,
  ...props
}: SkeletonProps) {
  return (
    <div
      className={cn(
        'rounded-md bg-muted',
        animate && 'animate-pulse',
        className
      )}
      {...props}
    />
  )
}

/**
 * Table Skeleton 組件
 * 用於表格載入狀態
 */
interface TableSkeletonProps {
  /**
   * 行數
   * @default 5
   */
  rows?: number;
  /**
   * 列數
   * @default 4
   */
  columns?: number;
  /**
   * 是否顯示標題行
   * @default true
   */
  showHeader?: boolean;
  /**
   * 自訂類名
   */
  className?: string;
}

function TableSkeleton({
  rows = 5,
  columns = 4,
  showHeader = true,
  className
}: TableSkeletonProps) {
  return (
    <div className={cn('space-y-4', className)}>
      {/* 表格標題 */}
      {showHeader && (
        <div className="grid gap-4" style={{ gridTemplateColumns: `repeat(${columns}, 1fr)` }}>
          {Array.from({ length: columns }).map((_, index) => (
            <Skeleton key={`header-${index}`} className="h-4" />
          ))}
        </div>
      )}
      
      {/* 表格內容行 */}
      {Array.from({ length: rows }).map((_, rowIndex) => (
        <div 
          key={`row-${rowIndex}`}
          className="grid gap-4"
          style={{ gridTemplateColumns: `repeat(${columns}, 1fr)` }}
        >
          {Array.from({ length: columns }).map((_, colIndex) => (
            <Skeleton key={`cell-${rowIndex}-${colIndex}`} className="h-6" />
          ))}
        </div>
      ))}
    </div>
  );
}

/**
 * Card Skeleton 組件
 * 用於卡片載入狀態
 */
interface CardSkeletonProps {
  /**
   * 是否顯示標題
   * @default true
   */
  showHeader?: boolean;
  /**
   * 內容行數
   * @default 3
   */
  lines?: number;
  /**
   * 自訂類名
   */
  className?: string;
}

function CardSkeleton({
  showHeader = true,
  lines = 3,
  className
}: CardSkeletonProps) {
  return (
    <div className={cn('rounded-lg border bg-card p-6 space-y-4', className)}>
      {/* 卡片標題 */}
      {showHeader && (
        <div className="space-y-2">
          <Skeleton className="h-6 w-1/3" />
          <Skeleton className="h-4 w-2/3" />
        </div>
      )}
      
      {/* 卡片內容 */}
      <div className="space-y-3">
        {Array.from({ length: lines }).map((_, index) => (
          <Skeleton
            key={`line-${index}`}
            className={cn(
              'h-4',
              index === lines - 1 ? 'w-1/2' : 'w-full'
            )}
          />
        ))}
      </div>
    </div>
  );
}

/**
 * Stats Skeleton 組件
 * 用於統計卡片載入狀態
 */
interface StatsSkeletonProps {
  /**
   * 統計卡片數量
   * @default 4
   */
  count?: number;
  /**
   * 自訂類名
   */
  className?: string;
}

function StatsSkeleton({
  count = 4,
  className
}: StatsSkeletonProps) {
  return (
    <div className={cn('grid gap-4 md:grid-cols-2 lg:grid-cols-4', className)}>
      {Array.from({ length: count }).map((_, index) => (
        <div key={`stat-${index}`} className="rounded-lg border bg-card p-6 space-y-4">
          <div className="flex items-center justify-between">
            <Skeleton className="h-4 w-20" />
            <Skeleton className="h-6 w-6 rounded" />
          </div>
          <div className="space-y-2">
            <Skeleton className="h-8 w-16" />
            <Skeleton className="h-3 w-24" />
          </div>
        </div>
      ))}
    </div>
  );
}

/**
 * List Skeleton 組件
 * 用於列表載入狀態
 */
interface ListSkeletonProps {
  /**
   * 列表項目數量
   * @default 6
   */
  items?: number;
  /**
   * 是否顯示頭像
   * @default false
   */
  showAvatar?: boolean;
  /**
   * 自訂類名
   */
  className?: string;
}

function ListSkeleton({
  items = 6,
  showAvatar = false,
  className
}: ListSkeletonProps) {
  return (
    <div className={cn('space-y-4', className)}>
      {Array.from({ length: items }).map((_, index) => (
        <div key={`item-${index}`} className="flex items-center space-x-4">
          {showAvatar && (
            <Skeleton className="h-12 w-12 rounded-full" />
          )}
          <div className="flex-1 space-y-2">
            <Skeleton className="h-4 w-3/4" />
            <Skeleton className="h-3 w-1/2" />
          </div>
          <Skeleton className="h-8 w-16" />
        </div>
      ))}
    </div>
  );
}

export {
  Skeleton,
  TableSkeleton,
  CardSkeleton,
  StatsSkeleton,
  ListSkeleton,
  type SkeletonProps,
  type TableSkeletonProps,
  type CardSkeletonProps,
  type StatsSkeletonProps,
  type ListSkeletonProps,
}
