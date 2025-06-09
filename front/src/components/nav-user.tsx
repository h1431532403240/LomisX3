"use client"

import {
  Bell,
  ChevronsUpDown,
  CreditCard,
  LogOut,
  Sparkles,
} from "lucide-react"
import { Link, useNavigate } from "react-router-dom"
import { useLogout } from "@/hooks/api/auth/useLogout"
import { useAuthStore } from "@/stores/authStore"

import {
  Avatar,
  AvatarFallback,
  AvatarImage,
} from "@/components/ui/avatar"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import {
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from "@/components/ui/sidebar"

/**
 * 使用者導覽組件
 * 顯示使用者資訊和相關操作選單
 * 
 * V2.0 版本更新：
 * - 新增完整的登出功能
 * - 整合 authStore 狀態清除
 * - 改善錯誤處理和使用者體驗
 */
export function NavUser({
  user,
}: {
  user: {
    name: string
    email: string
    avatar: string
  }
}) {
  const { isMobile } = useSidebar()
  const navigate = useNavigate()
  const logout = useAuthStore((state) => state.logout)
  const logoutMutation = useLogout()

  /**
   * 處理登出邏輯 V3.0 - 快速登出策略
   * 優先用戶體驗：立即清除本地狀態，背景調用 API
   * 
   * 流程：
   * 1. 立即清除本地認證狀態
   * 2. 立即重導向到登入頁
   * 3. 背景嘗試撤銷後端 token（非阻塞）
   */
  const handleLogout = async () => {
    console.log('🔓 開始快速登出流程...');
    
    // 1. 立即清除 authStore 狀態（優先用戶體驗）
    console.log('🧹 立即清除 authStore 狀態...');
    logout();
    
    // 2. 立即重導向到登入頁
    console.log('🔄 立即重導向到登入頁...');
    navigate('/login', { replace: true });
    
    console.log('✅ 前端登出完成');
    
    // 3. 背景嘗試調用後端 API 撤銷 token（非阻塞）
    // 使用 setTimeout 確保重導向先完成
    setTimeout(async () => {
      try {
        console.log('📡 背景調用後端登出 API...');
        await logoutMutation.mutateAsync();
        console.log('✅ 後端 token 撤銷成功');
      } catch (error) {
        console.warn('⚠️ 後端 token 撤銷失敗（忽略）:', error);
        // 不影響用戶體驗，token 過期後會自動失效
      }
    }, 100);
  }

  return (
    <SidebarMenu>
      <SidebarMenuItem>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <SidebarMenuButton
              size="lg"
              className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
            >
              <Avatar className="h-8 w-8 rounded-lg">
                <AvatarImage src={user.avatar} alt={user.name} />
                <AvatarFallback className="rounded-lg">
                  {user.name.slice(0, 2).toUpperCase()}
                </AvatarFallback>
              </Avatar>
              <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-semibold">{user.name}</span>
                <span className="truncate text-xs">{user.email}</span>
              </div>
              <ChevronsUpDown className="ml-auto size-4" />
            </SidebarMenuButton>
          </DropdownMenuTrigger>
          <DropdownMenuContent
            className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
            side={isMobile ? "bottom" : "right"}
            align="end"
            sideOffset={4}
          >
            <DropdownMenuLabel className="p-0 font-normal">
              <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                <Avatar className="h-8 w-8 rounded-lg">
                  <AvatarImage src={user.avatar} alt={user.name} />
                  <AvatarFallback className="rounded-lg">
                    {user.name.slice(0, 2).toUpperCase()}
                  </AvatarFallback>
                </Avatar>
                <div className="grid flex-1 text-left text-sm leading-tight">
                  <span className="truncate font-semibold">{user.name}</span>
                  <span className="truncate text-xs">{user.email}</span>
                </div>
              </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
              <DropdownMenuItem asChild>
                <Link to="/account">
                  <Sparkles />
                  帳號設定
                </Link>
              </DropdownMenuItem>
              <DropdownMenuItem asChild>
                <Link to="/account/billing">
                  <CreditCard />
                  帳單管理
                </Link>
              </DropdownMenuItem>
              <DropdownMenuItem asChild>
                <Link to="/account/notifications">
                  <Bell />
                  通知設定
                </Link>
              </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={handleLogout}
              disabled={logoutMutation.isPending}
              className="text-red-600 focus:text-red-600 focus:bg-red-50"
            >
              <LogOut />
              {logoutMutation.isPending ? '登出中...' : '登出'}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </SidebarMenuItem>
    </SidebarMenu>
  )
}
