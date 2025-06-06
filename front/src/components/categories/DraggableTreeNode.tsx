// React import removed for production build
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { 
  ChevronRight, 
  ChevronDown, 
  GripVertical,
  Folder,
  FolderOpen,
  File
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { components } from '@/types/api';

type ProductCategory = components['schemas']['ProductCategory'];

/**
 * 🎯 拖拽投影資訊
 */
interface ProjectedInfo {
  depth: number;
  maxDepth: number;
  minDepth: number;
  parentId: number | null;
}

/**
 * 🎯 可拖拽樹狀節點組件屬性
 */
export interface DraggableTreeNodeProps {
  id: number;
  category: ProductCategory;
  depth: number;
  isExpanded: boolean;
  isSelected: boolean;
  hasChildren: boolean;
  onToggleExpand: () => void;
  onSelect: () => void;
  projected?: ProjectedInfo | null;
  isDragOver: boolean;
}

/**
 * 🌲 可拖拽的樹狀節點組件
 * 支援拖拽排序、展開收合、選擇等功能
 */
export function DraggableTreeNode({
  id,
  category,
  depth,
  isExpanded,
  isSelected,
  hasChildren,
  onToggleExpand,
  onSelect,
  projected,
  isDragOver,
}: DraggableTreeNodeProps) {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({
    id: id,
  });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };

  // 🎨 計算樣式
  const isProjected = projected != null;
  const projectedDepth = isProjected ? projected.depth : depth;
  const displayDepth = Math.max(0, projectedDepth);

  return (
    <div
      ref={setNodeRef}
      style={{
        ...style,
        paddingLeft: `${displayDepth * 24 + 12}px`,
      }}
      className={cn(
        'group relative flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200',
        'hover:bg-muted/50 cursor-pointer select-none',
        {
          'opacity-50': isDragging,
          'bg-primary/10 border border-primary/20': isSelected,
          'bg-blue-50 border border-blue-200': isDragOver,
          'bg-yellow-50 border border-yellow-200': isProjected,
        }
      )}
      onClick={onSelect}
      {...attributes}
    >
      {/* 📁 展開/收合按鈕 */}
      <div className="flex items-center justify-center w-4 h-4">
        {hasChildren ? (
          <Button
            variant="ghost"
            size="sm"
            className="h-4 w-4 p-0 hover:bg-muted"
            onClick={(e) => {
              e.stopPropagation();
              onToggleExpand();
            }}
          >
            {isExpanded ? (
              <ChevronDown className="h-3 w-3" />
            ) : (
              <ChevronRight className="h-3 w-3" />
            )}
          </Button>
        ) : (
          <div className="w-3 h-3" />
        )}
      </div>

      {/* 🎯 拖拽控制點 */}
      <div
        className="flex items-center justify-center w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity cursor-grab active:cursor-grabbing"
        {...listeners}
      >
        <GripVertical className="h-3 w-3 text-muted-foreground" />
      </div>

      {/* 📂 圖示 */}
      <div className="flex items-center justify-center w-5 h-5">
        {hasChildren ? (
          isExpanded ? (
            <FolderOpen className="h-4 w-4 text-blue-500" />
          ) : (
            <Folder className="h-4 w-4 text-blue-500" />
          )
        ) : (
          <File className="h-4 w-4 text-muted-foreground" />
        )}
      </div>

      {/* 📝 分類資訊 */}
      <div className="flex-1 flex items-center gap-2 min-w-0">
        <span className="font-medium truncate">{category.name}</span>
        <span className="text-xs text-muted-foreground">/{category.slug}</span>
      </div>

      {/* 🏷️ 標籤 */}
      <div className="flex items-center gap-1">
        <Badge variant="outline" className="text-xs">
          {category.position ?? 0}
        </Badge>
        <Badge 
          variant={category.status ? 'default' : 'secondary'}
          className="text-xs"
        >
          {category.status ? '啟用' : '停用'}
        </Badge>
        {hasChildren && (
          <Badge variant="secondary" className="text-xs">
            {category.children?.length ?? 0}
          </Badge>
        )}
      </div>

      {/* 🎯 投影指示器 */}
      {isProjected && (
        <div className="absolute -left-2 top-0 bottom-0 w-1 bg-yellow-400 rounded-full" />
      )}
    </div>
  );
} 