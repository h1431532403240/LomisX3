/**
 * 🌳 樹狀資料處理工具函數
 * 提供商品分類樹狀結構的各種操作方法
 */

export interface TreeNode {
  id: number;
  name: string;
  parent_id: number | null;
  position: number;
  status: boolean;
  depth: number;
  children?: TreeNode[];
  ancestorIds?: number[];
}

export interface FlattenedItem extends TreeNode {
  ancestorIds: number[];
  hasChildren: boolean;
  collapsed?: boolean;
}

/**
 * 將樹狀結構扁平化為一維陣列
 * 用於 @dnd-kit 的 SortableContext
 */
export function flattenTree(
  items: TreeNode[],
  activeId?: number,
  overId?: number,
  ancestorIds: number[] = []
): FlattenedItem[] {
  return items.reduce<FlattenedItem[]>((acc, item) => {
    const hasChildren = Boolean(item.children && item.children.length > 0);
    const currentAncestorIds = [...ancestorIds];
    
    // 添加當前項目
    acc.push({
      ...item,
      ancestorIds: currentAncestorIds,
      hasChildren,
      collapsed: false, // 預設展開
    });

    // 遞歸處理子項目
    if (hasChildren && item.children) {
      const childItems = flattenTree(
        item.children,
        activeId,
        overId,
        [...currentAncestorIds, item.id]
      );
      acc.push(...childItems);
    }

    return acc;
  }, []);
}

/**
 * 從扁平化陣列重建樹狀結構
 */
export function buildTree(flattenedItems: FlattenedItem[]): TreeNode[] {
  const itemMap = new Map<number, TreeNode>();
  const rootItems: TreeNode[] = [];

  // 首先建立所有節點的映射
  flattenedItems.forEach(item => {
    itemMap.set(item.id, {
      ...item,
      children: [],
    });
  });

  // 然後建立父子關係
  flattenedItems.forEach(item => {
    const node = itemMap.get(item.id)!;
    
    if (item.parent_id === null) {
      rootItems.push(node);
    } else {
      const parent = itemMap.get(item.parent_id);
      if (parent) {
        parent.children = parent.children ?? [];
        parent.children.push(node);
      }
    }
  });

  return rootItems;
}

/**
 * 在樹狀結構中查找節點
 */
export function findTreeNode(items: TreeNode[], id: number): TreeNode | null {
  for (const item of items) {
    if (item.id === id) {
      return item;
    }
    
    if (item.children) {
      const found = findTreeNode(item.children, id);
      if (found) {
        return found;
      }
    }
  }
  
  return null;
}

/**
 * 移除樹狀結構中的節點
 */
export function removeTreeNode(items: TreeNode[], id: number): TreeNode[] {
  return items
    .filter(item => item.id !== id)
    .map(item => ({
      ...item,
      children: item.children ? removeTreeNode(item.children, id) : undefined,
    }));
}

/**
 * 獲取節點的所有後代 ID
 */
export function getDescendantIds(items: TreeNode[], id: number): number[] {
  const node = findTreeNode(items, id);
  if (!node?.children) {
    return [];
  }

  const descendants: number[] = [];
  
  function collectDescendants(children: TreeNode[]) {
    children.forEach(child => {
      descendants.push(child.id);
      if (child.children) {
        collectDescendants(child.children);
      }
    });
  }

  collectDescendants(node.children);
  return descendants;
}

/**
 * 檢查是否可以移動節點（避免循環引用）
 */
export function canMoveNode(
  items: TreeNode[],
  activeId: number,
  overId: number
): boolean {
  // 不能移動到自己
  if (activeId === overId) {
    return false;
  }

  // 不能移動到自己的後代
  const descendants = getDescendantIds(items, activeId);
  return !descendants.includes(overId);
}

/**
 * 計算節點的縮排等級
 */
export function getIndentationLevel(ancestorIds: number[]): number {
  return ancestorIds.length;
}

/**
 * 獲取節點的深度（從 0 開始）
 */
export function getNodeDepth(ancestorIds: number[]): number {
  return ancestorIds.length;
}

/**
 * 排序樹狀結構的子節點
 */
export function sortTreeNodes(items: TreeNode[]): TreeNode[] {
  const sorted = [...items].sort((a, b) => a.position - b.position);
  
  return sorted.map(item => ({
    ...item,
    children: item.children ? sortTreeNodes(item.children) : undefined,
  }));
}

/**
 * 更新節點在樹狀結構中的位置
 */
export function updateNodePositions(items: TreeNode[]): TreeNode[] {
  return items.map((item, index) => ({
    ...item,
    position: index + 1,
    children: item.children ? updateNodePositions(item.children) : undefined,
  }));
}

/**
 * 獲取節點的路徑字串（麵包屑）
 */
export function getNodePath(items: TreeNode[], id: number): string {
  function findPath(nodes: TreeNode[], targetId: number, currentPath: string[] = []): string[] | null {
    for (const node of nodes) {
      const newPath = [...currentPath, node.name];
      
      if (node.id === targetId) {
        return newPath;
      }
      
      if (node.children) {
        const found = findPath(node.children, targetId, newPath);
        if (found) {
          return found;
        }
      }
    }
    
    return null;
  }

  const path = findPath(items, id);
  return path ? path.join(' > ') : '';
} 