// React import removed for production build
import { Badge } from '@/components/ui/badge';
import { Folder } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * 樹狀節點覆蓋層組件 Props
 */
interface TreeNodeOverlayProps {
  name: string;
  id: number;
  depth: number;
  status?: boolean;
  hasChildren?: boolean;
  childrenCount?: number;
  className?: string;
}

/**
 * 樹狀節點覆蓋層組件
 * 
 * 用於在拖拽過程中顯示被拖拽節點的預覽
 * 包含簡化的節點資訊和視覺效果
 */
export function TreeNodeOverlay({
  name,
  id,
  depth: _depth,
  status = true,
  hasChildren = false,
  childrenCount = 0,
  className,
}: TreeNodeOverlayProps) {
  return (
    <div className={cn(
      'bg-background border border-primary shadow-2xl rounded-lg',
      'opacity-95 transform rotate-2 scale-105',
      'pointer-events-none select-none',
      className
    )}>
      <div className="flex items-center gap-2 py-3 px-3">
        {/* 拖拽指示圖示 */}
        <div className="w-6 flex items-center justify-center">
          <div className="w-1 h-4 bg-primary rounded-full" />
        </div>

        {/* 展開指示 */}
        <div className="w-6" />

        {/* 資料夾圖示 */}
        <div className={cn(
          'flex items-center justify-center w-8 h-8 rounded-md',
          hasChildren ? 'bg-blue-100 text-blue-600' : 'bg-muted'
        )}>
          {hasChildren ? (
            <Folder className="h-4 w-4" />
          ) : (
            <div className={cn(
              'w-3 h-3 rounded-full',
              status ? 'bg-green-500' : 'bg-gray-400'
            )} />
          )}
        </div>

        {/* 分類資訊 */}
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2">
            <span className={cn(
              'font-medium text-sm truncate',
              !status && 'text-muted-foreground line-through'
            )}>
              {name}
            </span>
            
            {/* 狀態標籤 */}
            <Badge 
              variant={status ? 'default' : 'secondary'} 
              className="text-xs"
            >
              {status ? '啟用' : '停用'}
            </Badge>
            
            {/* 子分類數量 */}
            {hasChildren && (
              <Badge variant="outline" className="text-xs">
                {childrenCount} 項
              </Badge>
            )}
          </div>
        </div>

        {/* 排序位置指示 */}
        <Badge variant="outline" className="text-xs min-w-fit">
          #{id}
        </Badge>
      </div>
    </div>
  );
} 