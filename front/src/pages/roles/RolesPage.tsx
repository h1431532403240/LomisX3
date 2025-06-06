/**
 * RolesPage - 角色管理頁面
 * 
 * 企業級角色管理系統
 * - 角色列表和管理
 * - 權限分配
 * - 角色層級管理
 * - 門市角色隔離
 * 
 * @author LomisX3 開發團隊
 * @version 1.0.0
 */

import React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { 
  Users, 
  Shield, 
  Crown, 
  UserCheck, 
  Settings, 
  Plus, 
  Search,
  MoreVertical,
  Edit,
  Trash2
} from 'lucide-react';
import { Input } from '@/components/ui/input';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import PermissionGuard from '@/components/common/permission-guard';
import { PageHeader } from '@/components/common/breadcrumb';
import { ResponsiveContainer } from '@/components/common/responsive-container';

/**
 * 模擬角色資料
 */
const mockRoles = [
  {
    id: 1,
    name: '系統管理員',
    slug: 'admin',
    description: '擁有系統所有權限的超級管理員',
    users_count: 2,
    permissions_count: 45,
    level: 'admin',
    color: 'bg-red-500'
  },
  {
    id: 2,
    name: '門市經理',
    slug: 'store-manager',
    description: '負責門市整體營運管理',
    users_count: 5,
    permissions_count: 32,
    level: 'manager',
    color: 'bg-blue-500'
  },
  {
    id: 3,
    name: '收銀員',
    slug: 'cashier',
    description: '負責收銀和客戶服務',
    users_count: 12,
    permissions_count: 18,
    level: 'staff',
    color: 'bg-green-500'
  },
  {
    id: 4,
    name: '庫存管理員',
    slug: 'inventory-manager',
    description: '負責商品庫存和採購管理',
    users_count: 3,
    permissions_count: 25,
    level: 'manager',
    color: 'bg-purple-500'
  },
];

/**
 * 角色管理頁面組件
 */
export default function RolesPage() {
  return (
    <PermissionGuard permissions={['roles.read']}>
      <ResponsiveContainer maxWidth="7xl" padding="default">
        <div className="space-y-6">
          {/* 頁面標題和麵包屑 */}
          <PageHeader
            title="角色管理"
            description="管理系統角色和權限分配"
            actions={
              <PermissionGuard permissions={['roles.create']}>
                <Button>
                  <Plus className="h-4 w-4 mr-2" />
                  新增角色
                </Button>
              </PermissionGuard>
            }
          />

        {/* 搜尋和篩選 */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Search className="h-5 w-5" />
              搜尋角色
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex gap-4">
              <Input placeholder="搜尋角色名稱或描述..." className="flex-1" />
              <Button variant="outline">搜尋</Button>
            </div>
          </CardContent>
        </Card>

        {/* 角色統計 */}
        <div className="grid gap-4 md:grid-cols-4">
          <Card>
            <CardContent className="flex items-center p-6">
              <Crown className="h-8 w-8 text-yellow-600 mr-4" />
              <div>
                <p className="text-2xl font-bold">4</p>
                <p className="text-sm text-muted-foreground">總角色數</p>
              </div>
            </CardContent>
          </Card>
          
          <Card>
            <CardContent className="flex items-center p-6">
              <Shield className="h-8 w-8 text-blue-600 mr-4" />
              <div>
                <p className="text-2xl font-bold">1</p>
                <p className="text-sm text-muted-foreground">管理員角色</p>
              </div>
            </CardContent>
          </Card>
          
          <Card>
            <CardContent className="flex items-center p-6">
              <UserCheck className="h-8 w-8 text-green-600 mr-4" />
              <div>
                <p className="text-2xl font-bold">22</p>
                <p className="text-sm text-muted-foreground">分配用戶</p>
              </div>
            </CardContent>
          </Card>
          
          <Card>
            <CardContent className="flex items-center p-6">
              <Settings className="h-8 w-8 text-purple-600 mr-4" />
              <div>
                <p className="text-2xl font-bold">45</p>
                <p className="text-sm text-muted-foreground">總權限數</p>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* 角色列表 */}
        <div className="grid gap-4">
          {mockRoles.map((role) => (
            <Card key={role.id} className="hover:shadow-md transition-shadow">
              <CardContent className="p-6">
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-4">
                    <Avatar className="h-12 w-12">
                      <AvatarFallback className={`${role.color} text-white`}>
                        {role.name.charAt(0)}
                      </AvatarFallback>
                    </Avatar>
                    
                    <div className="space-y-1">
                      <div className="flex items-center space-x-2">
                        <h3 className="font-semibold text-lg">{role.name}</h3>
                        <Badge variant={
                          role.level === 'admin' ? 'destructive' :
                          role.level === 'manager' ? 'default' : 'secondary'
                        }>
                          {role.level === 'admin' ? '管理員' :
                           role.level === 'manager' ? '經理' : '一般'}
                        </Badge>
                      </div>
                      
                      <p className="text-muted-foreground">{role.description}</p>
                      
                      <div className="flex items-center space-x-4 text-sm text-muted-foreground">
                        <span className="flex items-center">
                          <Users className="h-4 w-4 mr-1" />
                          {role.users_count} 位使用者
                        </span>
                        <span className="flex items-center">
                          <Shield className="h-4 w-4 mr-1" />
                          {role.permissions_count} 項權限
                        </span>
                      </div>
                    </div>
                  </div>

                  <PermissionGuard permissions={['roles.update', 'roles.delete']}>
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="sm">
                          <MoreVertical className="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem>
                          <Edit className="h-4 w-4 mr-2" />
                          編輯角色
                        </DropdownMenuItem>
                        <DropdownMenuItem>
                          <Shield className="h-4 w-4 mr-2" />
                          管理權限
                        </DropdownMenuItem>
                        <DropdownMenuItem className="text-red-600">
                          <Trash2 className="h-4 w-4 mr-2" />
                          刪除角色
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </PermissionGuard>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>

        {/* 開發中提示 */}
        <Card className="border-dashed">
          <CardContent className="flex flex-col items-center justify-center py-12">
            <Users className="h-12 w-12 text-muted-foreground mb-4" />
            <h3 className="text-lg font-semibold mb-2">角色管理功能開發中</h3>
            <p className="text-muted-foreground text-center max-w-md">
              完整的角色管理系統正在開發中，包含角色建立、權限分配、層級管理等功能。
            </p>
          </CardContent>
        </Card>
        </div>
      </ResponsiveContainer>
    </PermissionGuard>
  );
} 