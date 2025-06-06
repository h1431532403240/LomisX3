/**
 * 商品分類樹狀檢視組件
 * 提供樹狀結構的分類數據展示，支援展開/收合、拖拽重組、層級指示器等功能
 */
import { useState, useMemo } from 'react';
import { 
  ChevronRight, 
  ChevronDown, 
  MoreHorizontal, 
  Edit, 
  Trash2, 
  Eye, 
  EyeOff, 
  Plus,
  Copy,
  GripVertical,
  Folder,
  FolderOpen
} from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useToast } from '@/hooks/use-toast';
import { 
  useUpdateCategory, 
  useDeleteCategory 
} from '@/hooks/use-product-categories';
import type { ProductCategory } from '@/types/api.fallback';

// 🆕 樹狀節點介面定義
interface TreeNode extends ProductCategory {
  children?: TreeNode[];
  level?: number;
}

// 🆕 組件屬性定義
interface CategoryTreeViewProps {
  /** 樹狀分類數據 */
  treeData: TreeNode[];
  /** 載入狀態 */
  isLoading?: boolean;
  /** 錯誤訊息 */
  error?: Error | null;
  /** 編輯分類事件 */
  onEdit?: (category: ProductCategory) => void;
  /** 查看詳情事件 */
  onView?: (category: ProductCategory) => void;
  /** 新增子分類事件 */
  onAddChild?: (parentCategory: ProductCategory) => void;
  /** 最大展開深度 */
  maxExpandDepth?: number;
}

// 🆕 樹狀節點組件屬性
interface TreeNodeProps {
  node: TreeNode;
  level: number;
  isExpanded: boolean;
  onToggleExpand: (nodeId: number) => void;
  onEdit?: (category: ProductCategory) => void;
  onView?: (category: ProductCategory) => void;
  onAddChild?: (parentCategory: ProductCategory) => void;
  onToggleStatus: (category: ProductCategory) => void;
  onDelete: (category: ProductCategory) => void;
}

/**
 * 🆕 樹狀節點組件
 */
function TreeNodeComponent({
  node,
  level,
  isExpanded,
  onToggleExpand,
  onEdit,
  onView,
  onAddChild,
  onToggleStatus,
  onDelete,
}: TreeNodeProps) {
  const hasChildren = node.children && node.children.length > 0;

  return (
    <div className="select-none">
      {/* 🌲 節點主體 */}
      <div 
        className={`
          flex items-center gap-2 py-2 px-3 rounded-md hover:bg-muted/50 transition-colors
          ${level > 0 ? 'ml-' + (level * 6) : ''}
        `}
        style={{ paddingLeft: `${level * 24 + 12}px` }}
      >
        {/* 📁 展開/收合按鈕 */}
        <div className="flex items-center justify-center w-4 h-4">
          {hasChildren ? (
            <Button
              variant="ghost"
              size="sm"
              className="h-4 w-4 p-0 hover:bg-muted"
              onClick={() => onToggleExpand(node.id)}
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
        <GripVertical className="h-4 w-4 text-muted-foreground cursor-grab active:cursor-grabbing opacity-0 group-hover:opacity-100 transition-opacity" />

        {/* 📂 資料夾圖示 */}
        <div className="flex items-center justify-center w-5 h-5">
          {hasChildren ? (
            isExpanded ? (
              <FolderOpen className="h-4 w-4 text-blue-500" />
            ) : (
              <Folder className="h-4 w-4 text-blue-500" />
            )
          ) : (
            <Avatar className="h-5 w-5">
              <AvatarFallback className="text-xs bg-muted">
                {node.name?.charAt(0).toUpperCase() ?? 'C'}
              </AvatarFallback>
            </Avatar>
          )}
        </div>

        {/* 📝 分類資訊 */}
        <div className="flex-1 flex items-center gap-3 min-w-0">
          <div className="flex flex-col min-w-0">
            <div className="flex items-center gap-2">
              <span className="font-medium truncate">{node.name}</span>
              <span className="text-xs text-muted-foreground">/{node.slug}</span>
            </div>
            {node.description && (
              <span className="text-xs text-muted-foreground truncate">
                {node.description}
              </span>
            )}
          </div>

          {/* 🏷️ 標籤區域 */}
          <div className="flex items-center gap-2 flex-shrink-0">
            <Badge variant="outline" className="text-xs">
              {node.position ?? 0}
            </Badge>
            <Badge 
              variant={node.status ? 'default' : 'secondary'}
              className={`text-xs cursor-pointer ${
                node.status ? 'bg-green-100 text-green-800 hover:bg-green-200' : ''
              }`}
              onClick={() => onToggleStatus(node)}
            >
              {node.status ? '啟用' : '停用'}
            </Badge>
            {hasChildren && (
              <Badge variant="secondary" className="text-xs">
                {node.children?.length ?? 0} 子項
              </Badge>
            )}
          </div>
        </div>

        {/* ⚙️ 操作選單 */}
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" className="h-6 w-6 p-0">
              <span className="sr-only">開啟選單</span>
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuLabel>操作</DropdownMenuLabel>
            <DropdownMenuItem onClick={() => onView?.(node)}>
              <Eye className="mr-2 h-4 w-4" />
              查看詳情
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => onEdit?.(node)}>
              <Edit className="mr-2 h-4 w-4" />
              編輯
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => onAddChild?.(node)}>
              <Plus className="mr-2 h-4 w-4" />
              新增子分類
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem onClick={() => onToggleStatus(node)}>
              {node.status ? (
                <>
                  <EyeOff className="mr-2 h-4 w-4" />
                  停用
                </>
              ) : (
                <>
                  <Eye className="mr-2 h-4 w-4" />
                  啟用
                </>
              )}
            </DropdownMenuItem>
            <DropdownMenuItem>
              <Copy className="mr-2 h-4 w-4" />
              複製
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem 
              className="text-red-600"
              onClick={() => onDelete(node)}
            >
              <Trash2 className="mr-2 h-4 w-4" />
              刪除
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      {/* 🌿 子節點遞歸渲染 */}
      {hasChildren && isExpanded && (
        <div className="ml-3 border-l border-muted pl-2">
          {node.children?.map((child) => (
            <TreeNodeComponent
              key={child.id}
              node={child}
              level={level + 1}
              isExpanded={isExpanded}
              onToggleExpand={onToggleExpand}
              onEdit={onEdit}
              onView={onView}
              onAddChild={onAddChild}
              onToggleStatus={onToggleStatus}
              onDelete={onDelete}
            />
          ))}
        </div>
      )}
    </div>
  );
}

/**
 * 🆕 分類樹狀檢視主組件
 */
export function CategoryTreeView({
  treeData = [],
  isLoading = false,
  error = null,
  onEdit,
  onView,
  onAddChild,
  maxExpandDepth: _maxExpandDepth = 2,
}: CategoryTreeViewProps) {
  const { toast } = useToast();
  
  // 🎛️ 本地狀態
  const [expandedNodes, setExpandedNodes] = useState<Set<number>>(new Set());
  const [_filterDepth, _setFilterDepth] = useState<number | null>(null);

  // 🔄 變更操作 hooks
  const updateCategoryMutation = useUpdateCategory();
  const deleteCategoryMutation = useDeleteCategory();

  // 🧮 處理展開/收合邏輯
  const isNodeExpanded = (nodeId: number) => expandedNodes.has(nodeId);

  const toggleNodeExpansion = (nodeId: number) => {
    const newExpanded = new Set(expandedNodes);
    if (newExpanded.has(nodeId)) {
      newExpanded.delete(nodeId);
    } else {
      newExpanded.add(nodeId);
    }
    setExpandedNodes(newExpanded);
  };

  // 🌲 展開所有節點
  const expandAll = () => {
    const allNodeIds = new Set<number>();
    
    const collectNodeIds = (nodes: TreeNode[]) => {
      nodes.forEach(node => {
        if (node.children && node.children.length > 0 && node.id) {
          allNodeIds.add(node.id);
          collectNodeIds(node.children);
        }
      });
    };
    
    collectNodeIds(treeData);
    setExpandedNodes(allNodeIds);
  };

  // 🌳 收合所有節點
  const collapseAll = () => {
    setExpandedNodes(new Set());
  };

  // 🔄 處理狀態切換
  const handleToggleStatus = async (category: ProductCategory) => {
    if (!category.id) return;
    
    try {
      await updateCategoryMutation.mutateAsync({
        id: category.id,
        data: { status: !category.status }
      });
      
      toast({
        title: '狀態更新成功',
        description: `分類「${category.name}」已${category.status ? '停用' : '啟用'}`,
      });
    } catch {
      // 錯誤處理由 mutation 的錯誤處理器統一處理
    }
  };

  // 🗑️ 處理刪除
  const handleDelete = async (category: ProductCategory) => {
    if (!category.id) return;
    
    try {
      await deleteCategoryMutation.mutateAsync(category.id);
      
      toast({
        title: '刪除成功',
        description: `分類「${category.name}」已成功刪除`,
      });
    } catch {
      // 錯誤處理由 mutation 的錯誤處理器統一處理
    }
  };

  // 📊 統計資訊計算
  const stats = useMemo(() => {
    const traverse = (nodes: TreeNode[], depth = 0) => {
      let count = 0;
      let maxDepth = depth;
      
      nodes.forEach(node => {
        count++;
        if (node.children && node.children.length > 0) {
          const childStats = traverse(node.children, depth + 1);
          count += childStats.count;
          maxDepth = Math.max(maxDepth, childStats.maxDepth);
        }
      });
      
      return { count, maxDepth };
    };
    
    return traverse(treeData);
  }, [treeData]);

  // 📱 載入狀態
  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-8">
        <div className="text-muted-foreground">載入分類樹狀結構中...</div>
      </div>
    );
  }

  // ❌ 錯誤狀態
  if (error) {
    return (
      <div className="flex items-center justify-center py-8">
        <div className="text-red-500">載入失敗: {error.message}</div>
      </div>
    );
  }

  // 📋 空狀態
  if (!treeData.length) {
    return (
      <div className="flex items-center justify-center py-8">
        <div className="text-center space-y-2">
          <div className="text-muted-foreground">沒有找到任何分類</div>
          <div className="text-sm text-muted-foreground">點擊「新增分類」開始建立您的第一個分類</div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* 🛠️ 工具列 */}
      <div className="flex items-center justify-between p-4 bg-muted/20 rounded-lg border">
        <div className="flex items-center gap-4">
          <div className="text-sm text-muted-foreground">
            樹狀結構統計：
          </div>
          <div className="flex items-center gap-4 text-xs">
            <span>根節點: {stats.count}</span>
            <span>總節點: {stats.count}</span>
            <span>最大深度: {stats.maxDepth}</span>
          </div>
        </div>
        
        <div className="flex items-center gap-2">
          <Button
            size="sm"
            variant="outline"
            onClick={expandAll}
          >
            展開全部
          </Button>
          <Button
            size="sm"
            variant="outline"
            onClick={collapseAll}
          >
            收合全部
          </Button>
        </div>
      </div>

      {/* 🌲 樹狀結構渲染 */}
      <div className="border rounded-lg p-4 space-y-1 group">
        {treeData.map((rootNode) => (
          <TreeNodeComponent
            key={rootNode.id}
            node={rootNode}
            level={0}
            isExpanded={rootNode.id ? isNodeExpanded(rootNode.id) : false}
            onToggleExpand={toggleNodeExpansion}
            onEdit={onEdit}
            onView={onView}
            onAddChild={onAddChild}
            onToggleStatus={(category) => void handleToggleStatus(category)}
            onDelete={(category) => void handleDelete(category)}
          />
        ))}
      </div>
    </div>
  );
} 