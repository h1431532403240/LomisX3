/**
 * @file 商品分類管理頁面
 * @description 提供完整的分類管理功能，包含列表檢視、樹狀檢視、搜尋篩選等
 */
import { useState } from 'react';
import { Plus, Grid3X3, List } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { PermissionGuard } from '@/components/common/permission-guard';
import { PageHeader } from '@/components/common/breadcrumb';
import { ResponsiveContainer } from '@/components/common/responsive-container';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import {
  useCategories,
  useCategoryTree,
  useCategoryStatistics,
} from '@/hooks/use-product-categories';
import type { CategoryListParams, ProductCategory } from '@/types/api.fallback';
import { AdvancedFilters } from '@/components/categories/AdvancedFilters';
import { CategoryForm } from '@/components/categories/CategoryForm';

/**
 * 分類管理主頁面
 * 需要 categories.read 權限才能存取
 */
export function CategoriesPage() {
  const [filters, setFilters] = useState<Partial<CategoryListParams>>({});
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingCategory, setEditingCategory] = useState<ProductCategory | null>(null);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [activeView, setActiveView] = useState<'list' | 'tree'>('list');

  const { data: paginatedCategories, isLoading: isLoadingList } = useCategories(filters);
  const { data: categoryTree, isLoading: isLoadingTree } = useCategoryTree(true);
  const { data: statsData } = useCategoryStatistics();
  
  const categories = paginatedCategories?.data ?? [];
  const totalCategories = paginatedCategories?.meta?.total ?? 0;
  const stats = statsData?.data;

  const handleCreateNew = () => {
    setEditingCategory(null);
    setIsFormOpen(true);
  };
  
  const handleEdit = (category: ProductCategory) => {
    setEditingCategory(category);
    setIsFormOpen(true);
  };
  
  const handleFormSuccess = () => {
    setIsFormOpen(false);
    setEditingCategory(null);
  };
  
  const handleFormCancel = () => {
    setIsFormOpen(false);
    setEditingCategory(null);
  };

  const handleAction = (action: string, category: ProductCategory) => {
    switch (action) {
      case 'edit':
        handleEdit(category);
        break;
      case 'add-child':
        setEditingCategory({ ...category, id: undefined, parent_id: category.id || null } as any);
        setIsFormOpen(true);
        break;
      case 'delete':
        // 刪除邏輯由 CategoryListView 內部處理
        break;
      default:
        break;
    }
  };

  return (
    <PermissionGuard 
      permission="categories.read"
      fallback={
        <div className="flex items-center justify-center min-h-screen">
          <div className="text-center">
            <h1 className="text-2xl font-bold text-muted-foreground mb-2">無權限存取</h1>
            <p className="text-muted-foreground">您沒有權限存取商品分類管理功能</p>
          </div>
        </div>
      }
    >
      <ResponsiveContainer maxWidth="7xl" padding="default">
        <div className="flex flex-1 flex-col gap-6">
      {/* 頁面標題和麵包屑 */}
      <PageHeader
        title="商品分類管理"
        description="管理商品分類階層結構，支援拖拽排序和批次操作"
        actions={
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm" onClick={() => setActiveView(activeView === 'list' ? 'tree' : 'list')}>
              {activeView === 'list' ? <Grid3X3 className="h-4 w-4 mr-2" /> : <List className="h-4 w-4 mr-2" />}
              {activeView === 'list' ? '樹狀檢視' : '列表檢視'}
            </Button>
            <PermissionGuard permission="categories.create">
              <Button onClick={handleCreateNew}>
                <Plus className="h-4 w-4 mr-2" />
                新增分類
              </Button>
            </PermissionGuard>
          </div>
        }
      />

      {/* 統計資料卡片 */}
      <div className="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">總分類數</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total ?? 0}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">啟用分類</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.active ?? 0}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">最大深度</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.max_depth ?? 0}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">已選中</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{selectedIds.length}</div>
          </CardContent>
        </Card>
      </div>

      {/* 主要內容區域 */}
      <Card className="flex-1">
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>分類{activeView === 'list' ? '列表' : '樹狀結構'}</CardTitle>
            <Badge variant="outline">{activeView === 'list' ? totalCategories : categoryTree?.length ?? 0} 個項目</Badge>
          </div>
        </CardHeader>
        <CardContent className="p-0">
          <Tabs value={activeView} onValueChange={(value) => setActiveView(value as 'list' | 'tree')}>
            <div className="px-6 pb-4">
              <TabsList className="grid w-full grid-cols-2">
                <TabsTrigger value="list" className="flex items-center gap-2"><List className="h-4 w-4" />列表檢視</TabsTrigger>
                <TabsTrigger value="tree" className="flex items-center gap-2"><Grid3X3 className="h-4 w-4" />樹狀檢視</TabsTrigger>
              </TabsList>
            </div>
            <TabsContent value="list" className="mt-0 p-6">
              <div className="space-y-4">
                <AdvancedFilters
                  filters={filters}
                  onFiltersChange={(newFilters) => setFilters(newFilters)}
                  isLoading={isLoadingList}
                />
                {isLoadingList ? (
                  <div className="flex justify-center py-8">
                    <div className="text-center">載入中...</div>
                  </div>
                ) : categories.length > 0 ? (
                  <div className="bg-gray-100 p-4 rounded-lg">
                    <h3 className="font-bold mb-2">商品分類列表 ({categories.length} 筆)</h3>
                    <div className="space-y-2 max-h-64 overflow-y-auto">
                                           {categories.map((category) => (
                         <div key={category.id} className="flex items-center justify-between bg-white p-3 rounded border">
                           <div>
                             <h4 className="font-medium">{category.name}</h4>
                             <p className="text-sm text-gray-500">{category.slug}</p>
                           </div>
                           <div className="flex items-center gap-2">
                             <Badge variant={category.status ? 'default' : 'secondary'}>
                               {category.status ? '啟用' : '停用'}
                             </Badge>
                             <PermissionGuard permission="categories.update">
                               <Button size="sm" onClick={() => handleEdit(category as any)}>編輯</Button>
                             </PermissionGuard>
                           </div>
                         </div>
                       ))}
                    </div>
                  </div>
                ) : (
                  <div className="text-center py-8 text-gray-500">暫無分類數據</div>
                )}
              </div>
            </TabsContent>
            <TabsContent value="tree" className="mt-0 p-6">
              {isLoadingTree ? (
                <div className="flex justify-center py-8">
                  <div className="text-center">載入中...</div>
                </div>
              ) : categoryTree && categoryTree.length > 0 ? (
                <div className="bg-gray-100 p-4 rounded-lg">
                  <h3 className="font-bold mb-2">分類樹狀結構 ({categoryTree.length} 個根節點)</h3>
                  <div className="space-y-2 max-h-64 overflow-y-auto">
                                         {categoryTree.map((category) => (
                       <div key={category.id} className="bg-white p-3 rounded border">
                         <div className="flex items-center justify-between">
                           <div>
                             <h4 className="font-medium">{category.name}</h4>
                             <p className="text-sm text-gray-500">深度: {category.depth || 0}</p>
                           </div>
                           <PermissionGuard permission="categories.update">
                             <Button size="sm" onClick={() => handleEdit(category as any)}>編輯</Button>
                           </PermissionGuard>
                         </div>
                       </div>
                     ))}
                  </div>
                </div>
              ) : (
                <div className="text-center py-8 text-gray-500">暫無樹狀結構數據</div>
              )}
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>

      {/* 新增/編輯彈出視窗 */}
      <Dialog open={isFormOpen} onOpenChange={setIsFormOpen}>
        <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{editingCategory ? '編輯分類' : '新增分類'}</DialogTitle>
          </DialogHeader>
          <CategoryForm
            category={editingCategory}
            onSuccess={handleFormSuccess}
            onCancel={handleFormCancel}
          />
        </DialogContent>
      </Dialog>
    </div>
    </ResponsiveContainer>
    </PermissionGuard>
  );
}

export default CategoriesPage; 