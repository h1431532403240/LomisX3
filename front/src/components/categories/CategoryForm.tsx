/**
 * @file 商品分類表單組件
 * @description 支援新增和編輯模式，包含完整的欄位驗證和錯誤處理
 */
import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Form, FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Loader2 } from 'lucide-react';
import { useCreateCategory, useUpdateCategory, useCategoryTree } from '@/hooks/use-product-categories';
import { useToast } from '@/hooks/use-toast';
import type { components } from '@/types/api';

type ProductCategory = components['schemas']['ProductCategory'];
type UpdateCategoryRequest = components['schemas']['UpdateCategoryRequest'];
type CreateCategoryRequest = components['schemas']['CreateCategoryRequest'];

interface TreeNode extends ProductCategory {
  depth: number;
}

const flattenTree = (nodes: ProductCategory[], depth = 0): TreeNode[] => {
  return nodes.reduce<TreeNode[]>((acc, node) => {
    acc.push({ ...node, depth });
    if (node.children) {
      acc.push(...flattenTree(node.children, depth + 1));
    }
    return acc;
  }, []);
};


const categoryFormSchema = z.object({
  name: z.string().min(1, '分類名稱不能為空').max(255, '分類名稱不能超過 255 個字元'),
  slug: z.string().min(1, 'URL 識別碼不能為空').max(255, 'URL 識別碼不能超過 255 個字元').regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/, 'URL 識別碼格式不正確'),
  description: z.string().optional().nullable(),
  parent_id: z.number().nullable().optional(),
  status: z.boolean(),
});

type CategoryFormData = Omit<z.infer<typeof categoryFormSchema>, 'position'>;


interface CategoryFormProps {
  category?: ProductCategory | null;
  onSuccess?: () => void;
  onCancel?: () => void;
}

export function CategoryForm({ category, onSuccess, onCancel }: CategoryFormProps) {
  const { toast } = useToast();
  const createCategoryMutation = useCreateCategory();
  const updateCategoryMutation = useUpdateCategory();
  const { data: categoryTree } = useCategoryTree(false); // Fetch all categories for parent selection

  const form = useForm<CategoryFormData>({
    resolver: zodResolver(categoryFormSchema),
    defaultValues: {
      name: category?.name ?? '',
      slug: category?.slug ?? '',
      description: category?.description ?? '',
      parent_id: category?.parent_id ?? null,
      status: category?.status ?? true,
    },
  });

  const watchName = form.watch('name');
  useEffect(() => {
    if (!category && watchName) {
      const autoSlug = watchName.toLowerCase().trim().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
      form.setValue('slug', autoSlug, { shouldValidate: true });
    }
  }, [watchName, category, form]);
  
  const handleSuccess = () => {
    toast({
      title: category ? '✅ 更新成功' : '✅ 新增成功',
      description: `分類 "${form.getValues('name')}" 已成功儲存。`,
    });
    onSuccess?.();
  };
  
  const handleError = (error: Error) => {
    toast({
      title: '❌ 操作失敗',
      description: error.message || '發生未知錯誤，請稍後重試。',
      variant: 'destructive',
    });
  };

  const onSubmit = (data: CategoryFormData) => {
    if (category && category.id) {
      const apiData: UpdateCategoryRequest = { ...data };
      updateCategoryMutation.mutate({ id: category.id, data: apiData }, { onSuccess: handleSuccess, onError: handleError });
    } else {
      // 確保 name 和 slug 存在，以符合 CreateCategoryRequest 型別
      if (typeof data.name === 'string' && typeof data.slug === 'string') {
        const apiData: CreateCategoryRequest = {
          ...data,
          name: data.name,
          slug: data.slug,
        };
        createCategoryMutation.mutate(apiData, { onSuccess: handleSuccess, onError: handleError });
      } else {
        handleError(new Error("分類名稱和 URL 識別碼是必需的。"));
      }
    }
  };

  const availableParentCategories = React.useMemo(() => {
    if (!categoryTree) return [];
    const flatCategories = flattenTree(categoryTree);
    if (!category || !category.id) return flatCategories;
    
    const descendantIds = new Set<number>();
    const getDescendants = (catId: number) => {
      descendantIds.add(catId);
      const children = flatCategories.filter(c => c.parent_id === catId);
      children.forEach(child => child.id && getDescendants(child.id));
    };
    getDescendants(category.id);

    return flatCategories.filter(c => c.id && !descendantIds.has(c.id));
  }, [categoryTree, category]);

  const isLoading = createCategoryMutation.isPending || updateCategoryMutation.isPending;

  return (
    <div className="max-w-2xl mx-auto">
      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-8">
          <Card>
            <CardHeader>
              <CardTitle>{category ? '編輯分類' : '新增分類'}</CardTitle>
              <CardDescription>{category ? `編輯「${category.name}」的詳細資訊` : '建立一個新的商品分類'}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <FormField control={form.control} name="name" render={({ field }) => (
                  <FormItem>
                    <FormLabel>分類名稱 *</FormLabel>
                    <FormControl><Input placeholder="例如：電子產品" {...field} /></FormControl>
                    <FormMessage />
                  </FormItem>
                )} />
                <FormField control={form.control} name="slug" render={({ field }) => (
                  <FormItem>
                    <FormLabel>URL 識別碼 *</FormLabel>
                    <FormControl><Input placeholder="例如：electronics" {...field} /></FormControl>
                    <FormDescription>用於 URL，建議使用英文、數字和連字符</FormDescription>
                    <FormMessage />
                  </FormItem>
                )} />
              </div>
              <FormField control={form.control} name="description" render={({ field }) => (
                <FormItem>
                  <FormLabel>描述</FormLabel>
                  <FormControl><Textarea placeholder="輸入分類的詳細描述..." {...field} value={field.value ?? ''} /></FormControl>
                  <FormMessage />
                </FormItem>
              )} />
              <FormField control={form.control} name="parent_id" render={({ field }) => (
                <FormItem>
                  <FormLabel>父分類</FormLabel>
                  <Select onValueChange={(value) => field.onChange(value ? parseInt(value) : null)} defaultValue={field.value?.toString()}>
                    <FormControl>
                      <SelectTrigger>
                        <SelectValue placeholder="選擇一個父分類（可選）" />
                      </SelectTrigger>
                    </FormControl>
                    <SelectContent>
                      <SelectItem value="">無父分類 (設為根分類)</SelectItem>
                      {availableParentCategories.map((cat) => (
                        cat.id && (
                          <SelectItem key={cat.id} value={cat.id.toString()}>
                            {'--'.repeat(cat.depth ?? 0)} {cat.name}
                          </SelectItem>
                        )
                      ))}
                    </SelectContent>
                  </Select>
                  <FormMessage />
                </FormItem>
              )} />
              <div className="grid grid-cols-2 gap-6">
                 {/* Position field removed as it's handled by drag-and-drop */}
                <FormField control={form.control} name="status" render={({ field }) => (
                  <FormItem className="flex flex-col">
                    <FormLabel>狀態</FormLabel>
                    <div className="flex items-center space-x-2 pt-2">
                      <FormControl><Switch checked={field.value} onCheckedChange={field.onChange} /></FormControl>
                      <FormLabel>{field.value ? '啟用' : '停用'}</FormLabel>
                    </div>
                    <FormMessage />
                  </FormItem>
                )} />
              </div>
            </CardContent>
          </Card>
          <div className="flex justify-end gap-2">
            {onCancel && <Button type="button" variant="outline" onClick={onCancel}>取消</Button>}
            <Button type="submit" disabled={isLoading}>
              {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {category ? '儲存變更' : '建立分類'}
            </Button>
          </div>
        </form>
      </Form>
    </div>
  );
} 