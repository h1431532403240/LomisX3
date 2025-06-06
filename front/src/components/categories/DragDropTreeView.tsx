/**
 * 🌳 拖拽排序樹狀檢視組件
 * 支援階層式分類的拖拽重新排序功能
 * 基於 @dnd-kit 實作，提供流暢的拖拽體驗
 */

import React, { useState, useMemo, useCallback } from 'react';
import {
  DndContext,
  KeyboardSensor,
  MeasuringStrategy,
  PointerSensor,
  closestCenter,
  rectIntersection,
  useSensor,
  useSensors,
  DragOverlay,
} from '@dnd-kit/core';
import type {
  DragEndEvent,
  DragMoveEvent,
  DragOverEvent,
  DragStartEvent,
  UniqueIdentifier,
} from '@dnd-kit/core';
import {
  SortableContext,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { useQueryClient } from '@tanstack/react-query';

import type { components } from '@/types/api';
import { useCategoryTree, useSortCategories, CATEGORY_KEYS } from '@/hooks/use-product-categories';
import { DraggableTreeNode } from './DraggableTreeNode';
import { TreeNodeOverlay } from './TreeNodeOverlay';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import {
  AlertCircle,
  Maximize2,
  Minimize2,
  RotateCcw,
  Trees,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

type ProductCategory = components['schemas']['ProductCategory'];
type SortPayload = components['schemas']['SortCategoriesRequest']['categories'];

/**
 * 拖拽項目的型別定義
 */
interface DragItem {
  id: number;
  category: ProductCategory;
  depth: number;
  parentId: number | null;
}

/**
 * 樹狀結構拖拽檢視組件
 *
 * 功能特色：
 * - 支援同層級拖拽排序
 * - 支援跨層級拖拽移動
 * - 視覺化拖拽預覽和放置提示
 * - 樂觀更新和錯誤回滾
 * - 展開/收合狀態管理
 * - 深度限制檢查
 */
export const DragDropTreeView: React.FC<{
  activeOnly?: boolean;
  onSelectCategory?: (category: ProductCategory) => void;
  selectedCategoryId?: number;
  className?: string;
}> = ({
  activeOnly = false,
  onSelectCategory,
  selectedCategoryId,
  className,
}) => {
  // TanStack Query 客戶端
  const queryClient = useQueryClient();
  const { toast } = useToast();

  // API 狀態管理
  const {
    data: tree,
    isLoading,
    error,
    refetch,
  } = useCategoryTree(activeOnly);
  
  const sortCategoriesMutation = useSortCategories();

  // 拖拽狀態管理
  const [activeId, setActiveId] = useState<UniqueIdentifier | null>(null);
  const [overId, setOverId] = useState<UniqueIdentifier | null>(null);
  const [offsetLeft, setOffsetLeft] = useState(0);
  const [expandedIds, setExpandedIds] = useState<Set<number>>(new Set());

  // 樹狀結構處理
  const { flattenedTree, projected, activeItem } = useMemo(() => {
    if (!tree) {
      return { flattenedTree: [], projected: null, activeItem: null };
    }

    const flattenTree = (
      items: ProductCategory[],
      parentId: number | null = null,
      depth = 0
    ): DragItem[] => {
      return items.reduce<DragItem[]>((acc, item) => {
        if (!item?.id) return acc; // 安全檢查
        const isExpanded = expandedIds.has(item.id);

        acc.push({
          id: item.id,
          category: item,
          depth,
          parentId,
        });

        if (item.children && item.children.length > 0 && isExpanded) {
          acc.push(...flattenTree(item.children, item.id, depth + 1));
        }

        return acc;
      }, []);
    };

    const flattened = flattenTree(tree);
    const currentActiveItem = activeId ? flattened.find(({ id }) => id === activeId) ?? null : null;

    let projectedPosition: {
      depth: number;
      parentId: number | null;
      maxDepth: number;
      minDepth: number;
    } | null = null;

    if (currentActiveItem && overId) {
      const overIndex = flattened.findIndex(({ id }) => id === overId);
      const overItem = flattened[overIndex];

      if (overItem) {
        const maxDepth = 5;
        const minDepth = 0;
        
        const projectedDepth = overItem.depth + Math.round(offsetLeft / 50);
        const targetDepth = Math.max(minDepth, Math.min(projectedDepth, maxDepth));

        const findParent = (startIndex: number, depth: number): ProductCategory | null => {
           if (depth === 0) return null;
           for (let i = startIndex; i >= 0; i--) {
            if (flattened[i].depth === depth - 1) {
              return flattened[i].category;
            }
          }
          return null;
        };
        
        const newParent = findParent(overIndex, targetDepth);
        
        projectedPosition = {
          depth: targetDepth,
          parentId: newParent?.id ?? null,
          maxDepth: 5,
          minDepth: 0,
        };
      }
    }

    return {
      flattenedTree: flattened,
      projected: projectedPosition,
      activeItem: currentActiveItem
    };
  }, [tree, expandedIds, activeId, overId, offsetLeft]);

  // 拖拽感應器配置
  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 10,
      },
    }),
    useSensor(KeyboardSensor)
  );

  const collisionDetectionStrategy = useCallback((args: Parameters<typeof closestCenter>[0]) => {
    const closestCenterCollisions = closestCenter(args);
    if (closestCenterCollisions.length > 0) return closestCenterCollisions;
    // @ts-ignore dnd-kit type issue
    return rectIntersection(args);
  }, []);
  
  const resetDragState = useCallback(() => {
    setActiveId(null);
    setOverId(null);
    setOffsetLeft(0);
    document.body.style.setProperty('cursor', '');
  }, []);

  const handleDragStart = useCallback(({ active }: DragStartEvent) => {
    setActiveId(active.id);
    setOverId(active.id);
    document.body.style.setProperty('cursor', 'grabbing');
  }, []);

  const handleDragMove = useCallback(({ delta }: DragMoveEvent) => {
    setOffsetLeft(delta.x);
  }, []);

  const handleDragOver = useCallback(({ over }: DragOverEvent) => {
    setOverId(over?.id ?? null);
  }, []);

  const handleDragEnd = useCallback(async ({ over }: DragEndEvent) => {
    resetDragState();

    if (!projected || !over || !activeId || !activeItem || !tree) {
      return;
    }

    const numericActiveId = Number(activeId);
    if (numericActiveId === over.id && projected.parentId === activeItem.parentId) {
      return;
    }

    const queryKey = CATEGORY_KEYS.tree(activeOnly);
    const previousTree = queryClient.getQueryData<ProductCategory[]>(queryKey);

    // 樂觀更新 UI
    let optimisticNewTree: ProductCategory[] = tree;
    queryClient.setQueryData<ProductCategory[]>(queryKey, (oldTree) => {
      if (!oldTree) return [];
      
      const removeItem = (items: ProductCategory[], idToRemove: number): ProductCategory[] => 
        items.map(i => ({...i})).filter(i => i.id !== idToRemove).map(i => {
          if (i.children) i.children = removeItem(i.children, idToRemove);
          return i;
        });

      const addItem = (items: ProductCategory[], itemToAdd: ProductCategory, pId: number | null, index: number): ProductCategory[] => {
        if (pId === null) return [...items.slice(0, index), itemToAdd, ...items.slice(index)];
        return items.map(i => {
          if (i.id === pId) {
            i.children = [...(i.children ?? []).slice(0, index), itemToAdd, ...(i.children ?? []).slice(index)];
          } else if (i.children) {
            i.children = addItem(i.children, itemToAdd, pId, index);
          }
          return i;
        });
      };
      
      const treeWithoutItem = removeItem(oldTree, numericActiveId);
      const newParentId = projected.parentId;
      const siblings = flattenedTree.filter(item => item.parentId === newParentId);
      const newIndex = siblings.findIndex(item => item.id === over.id);

      optimisticNewTree = addItem(treeWithoutItem, activeItem.category, newParentId, newIndex >= 0 ? newIndex : siblings.length);
      return optimisticNewTree;
    });

    // 準備 API payload
    try {
      const finalTree = queryClient.getQueryData<ProductCategory[]>(queryKey) ?? optimisticNewTree;
      const getSiblings = (items: ProductCategory[], parentId: number | null): ProductCategory[] => {
          if(parentId === null) return items;
          for (const item of items) {
              if (item.id === parentId) return item.children ?? [];
              if (item.children) {
                  const found = getSiblings(item.children, parentId);
                  if (found.length > 0) return found;
              }
          }
          return [];
      }

      const siblingsToSort = getSiblings(finalTree, projected.parentId);
      const sortPayload: SortPayload = siblingsToSort.map((cat, index) => ({
        id: cat.id,
        position: index + 1,
        parent_id: projected.parentId,
      }));

      if (sortPayload.length > 0) {
        await sortCategoriesMutation.mutateAsync({ categories: sortPayload });
        toast({ title: "✅ 分類順序已成功儲存" });
      } else {
        // 如果沒有同級節點可排序（例如，拖曳到空層級），也需要更新被拖曳的項目本身
         await sortCategoriesMutation.mutateAsync({ categories: [{
            id: numericActiveId,
            position: 1,
            parent_id: projected.parentId,
         }] });
         toast({ title: "✅ 分類已移動" });
      }

    } catch (apiError) {
      toast({
        title: "❌ 更新失敗",
        description: "無法儲存分類順序，請重試。",
        variant: "destructive",
      });
      if(previousTree) {
        queryClient.setQueryData(queryKey, previousTree);
      }
    } finally {
      await queryClient.invalidateQueries({ queryKey });
    }
  }, [
    activeId,
    activeItem,
    projected,
    tree,
    flattenedTree,
    queryClient,
    sortCategoriesMutation,
    resetDragState,
    activeOnly,
    toast,
  ]);

  const toggleAll = (expand: boolean) => {
    if (expand && tree) {
      const allIds = new Set<number>();
      const collectIds = (items: ProductCategory[]) => {
        for (const item of items) {
          if (item.children && item.children.length > 0) {
            if(item.id) allIds.add(item.id);
            collectIds(item.children);
          }
        }
      };
      collectIds(tree);
      setExpandedIds(allIds);
    } else {
      setExpandedIds(new Set());
    }
  };

  if (isLoading) {
    return (
      <div className={cn("space-y-2", className)}>
        {[...Array(5)].map((_, i) => (
          <Skeleton key={i} className="h-12 w-full" />
        ))}
      </div>
    );
  }

  if (error) {
    return (
      <div className={cn("flex flex-col items-center justify-center p-8 border rounded-md bg-destructive/10 text-destructive", className)}>
        <AlertCircle className="h-8 w-8 mb-4" />
        <p className="font-semibold">讀取分類時發生錯誤</p>
        <p className="text-sm mb-4">{error.message}</p>
        <Button onClick={() => void refetch()} variant="secondary" size="sm">
          <RotateCcw className="mr-2 h-4 w-4" />
          重試
        </Button>
      </div>
    );
  }

  if (!tree || tree.length === 0) {
     return (
      <div className={cn("flex flex-col items-center justify-center p-8 border-2 border-dashed rounded-md text-muted-foreground", className)}>
        <Trees className="h-10 w-10 mb-4" />
        <h3 className="text-lg font-semibold">沒有分類資料</h3>
        <p className="text-sm">請先新增您的第一個商品分類。</p>
      </div>
    );
  }

  return (
    <div className={cn("space-y-4", className)}>
      <div className="flex items-center gap-2">
        <Button onClick={() => toggleAll(true)} variant="outline" size="sm">
          <Maximize2 className="mr-2 h-4 w-4" />
          全部展開
        </Button>
        <Button onClick={() => toggleAll(false)} variant="outline" size="sm">
          <Minimize2 className="mr-2 h-4 w-4" />
          全部收合
        </Button>
      </div>
      
      <DndContext
        sensors={sensors}
        collisionDetection={collisionDetectionStrategy}
        onDragStart={handleDragStart}
        onDragMove={handleDragMove}
        onDragOver={handleDragOver}
        onDragEnd={handleDragEnd}
        onDragCancel={resetDragState}
        measuring={{ droppable: { strategy: MeasuringStrategy.Always } }}
      >
        <SortableContext items={flattenedTree.map(({ id }) => id)} strategy={verticalListSortingStrategy}>
          <div className="space-y-1">
            {flattenedTree.map(({ id, category }) => {
              if(!category?.id) return null;
              return (
              <DraggableTreeNode
                key={id}
                id={id}
                category={category}
                depth={flattenedTree.find(item => item.id === id)?.depth ?? 0}
                hasChildren={!!(category.children && category.children.length > 0)}
                isExpanded={expandedIds.has(category.id)}
                onToggleExpand={() =>
                  setExpandedIds((prev) => {
                    if(!category.id) return prev;
                    const next = new Set(prev);
                    if (next.has(category.id)) {
                      next.delete(category.id);
                    } else {
                      next.add(category.id);
                    }
                    return next;
                  })
                }
                isSelected={selectedCategoryId === category.id}
                onSelect={() => onSelectCategory?.(category)}
                isDragOver={overId === id}
                projected={
                  activeId === id && projected
                    ? projected
                    : null
                }
              />
            )})}
          </div>
        </SortableContext>

        <DragOverlay dropAnimation={null}>
          {activeItem ? (
            <TreeNodeOverlay
              id={activeItem.category.id ?? 0}
              name={activeItem.category.name ?? '未命名分類'}
              status={activeItem.category.status}
              depth={activeItem.depth}
              childrenCount={activeItem.category.children?.length ?? 0}
              hasChildren={!!(activeItem.category.children && activeItem.category.children.length > 0)}
            />
          ) : null}
        </DragOverlay>
      </DndContext>
    </div>
  );
}; 