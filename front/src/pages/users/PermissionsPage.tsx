/**
 * PermissionsPage - 權限管理頁面
 * 
 * 企業級權限管理系統
 * - 權限列表和樹狀結構
 * - 權限分配和管理
 * - 角色權限映射
 * - 門市權限隔離
 * 
 * @author LomisX3 開發團隊
 * @version 1.0.0
 */

import React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Shield, Settings, Users, Lock, Plus, Search } from 'lucide-react';
import { Input } from '@/components/ui/input';
import PermissionGuard from '@/components/common/permission-guard';

/**
 * 權限管理頁面組件
 */
export default function PermissionsPage() {
  return (
    <PermissionGuard permissions={['permissions.read']}>
      <div className="container mx-auto p-6 space-y-6">
        {/* 頁面標題 */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold tracking-tight flex items-center gap-2">
              <Shield className="h-8 w-8 text-blue-600" />
              權限管理
            </h1>
            <p className="text-muted-foreground mt-2">
              管理系統權限和角色分配
            </p>
          </div>
          
          <PermissionGuard permissions={['permissions.create']}>
            <Button>
              <Plus className="h-4 w-4 mr-2" />
              新增權限
            </Button>
          </PermissionGuard>
        </div>

        {/* 搜尋和篩選 */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Search className="h-5 w-5" />
              搜尋權限
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex gap-4">
              <Input placeholder="搜尋權限名稱或描述..." className="flex-1" />
              <Button variant="outline">搜尋</Button>
            </div>
          </CardContent>
        </Card>

        {/* 權限分類 */}
        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          {/* 使用者管理權限 */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Users className="h-5 w-5 text-blue-600" />
                使用者管理
              </CardTitle>
              <CardDescription>
                使用者相關的權限設定
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <span className="text-sm">users.read</span>
                  <Badge variant="secondary">讀取</Badge>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-sm">users.create</span>
                  <Badge variant="default">建立</Badge>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-sm">users.update</span>
                  <Badge variant="outline">更新</Badge>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-sm">users.delete</span>
                  <Badge variant="destructive">刪除</Badge>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* 角色管理權限 */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Shield className="h-5 w-5 text-green-600" />
                角色管理
              </CardTitle>
              <CardDescription>
                角色相關的權限設定
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <span className="text-sm">roles.read</span>
                  <Badge variant="secondary">讀取</Badge>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-sm">roles.create</span>
                  <Badge variant="default">建立</Badge>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-sm">roles.update</span>
                  <Badge variant="outline">更新</Badge>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-sm">roles.delete</span>
                  <Badge variant="destructive">刪除</Badge>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* 系統管理權限 */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Settings className="h-5 w-5 text-purple-600" />
                系統管理
              </CardTitle>
              <CardDescription>
                系統相關的權限設定
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <span className="text-sm">system.read</span>
                  <Badge variant="secondary">讀取</Badge>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-sm">system.config</span>
                  <Badge variant="default">配置</Badge>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-sm">system.backup</span>
                  <Badge variant="outline">備份</Badge>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-sm">system.logs</span>
                  <Badge variant="secondary">日誌</Badge>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* 開發中提示 */}
        <Card className="border-dashed">
          <CardContent className="flex flex-col items-center justify-center py-12">
            <Lock className="h-12 w-12 text-muted-foreground mb-4" />
            <h3 className="text-lg font-semibold mb-2">權限管理功能開發中</h3>
            <p className="text-muted-foreground text-center max-w-md">
              完整的權限管理系統正在開發中，包含動態權限樹、角色分配、細粒度控制等功能。
            </p>
          </CardContent>
        </Card>
      </div>
    </PermissionGuard>
  );
} 