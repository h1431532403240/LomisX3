import * as React from "react"
import {
  ArrowUpCircleIcon,
  BarChartIcon,
  DatabaseIcon,
  FileTextIcon,
  HelpCircleIcon,
  LayoutDashboardIcon,
  SearchIcon,
  SettingsIcon,
  UsersIcon,
  ShoppingBagIcon,
  ShoppingCartIcon,
  TrendingUpIcon,
  CalendarIcon,
} from "lucide-react"

import { NavDocuments } from "@/components/nav-documents"
import { NavMain } from "@/components/nav-main"
import { NavSecondary } from "@/components/nav-secondary"
import { NavUser } from "@/components/nav-user"
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar"

/**
 * LomisX3 側邊欄資料配置
 * 定義系統的導覽結構和使用者資訊
 */
const data = {
  user: {
    name: "管理員",
    email: "admin@lomisx3.com",
    avatar: "/avatars/shadcn.jpg",
  },
  navMain: [
    {
      title: "Dashboard",
      url: "/dashboard",
      icon: LayoutDashboardIcon,
    },
    {
      title: "商品管理",
      url: "/products",
      icon: ShoppingBagIcon,
      items: [
        {
          title: "商品列表",
          url: "/products",
          permission: "products.read",
        },
        {
          title: "新增商品",
          url: "/products/new",
          permission: "products.create",
        },
        {
          title: "商品分類",
          url: "/products/categories",
          permission: "categories.read",
        },
        {
          title: "庫存管理",
          url: "/products/inventory",
          permission: "products.inventory",
        },
      ],
    },
    {
      title: "訂單管理",
      url: "/orders",
      icon: ShoppingCartIcon,
      items: [
        {
          title: "訂單列表",
          url: "/orders",
          permission: "orders.read",
        },
        {
          title: "待處理訂單",
          url: "/orders/pending",
          permission: "orders.read",
        },
        {
          title: "已完成訂單",
          url: "/orders/completed",
          permission: "orders.read",
        },
        {
          title: "退款申請",
          url: "/orders/refunds",
          permission: "orders.refunds",
        },
      ],
    },
    {
      title: "使用者管理",
      url: "/users",
      icon: UsersIcon,
      items: [
        {
          title: "使用者列表",
          url: "/users",
          permission: "users.read",
        },
        {
          title: "新增使用者",
          url: "/users/create",
          permission: "users.create",
        },
        {
          title: "權限管理",
          url: "/users/permissions",
          permission: "users.permissions",
        },
        {
          title: "角色管理",
          url: "/roles",
          permission: "roles.read",
        },
      ],
    },
  ],
  navClouds: [
    {
      title: "分析報表",
      icon: BarChartIcon,
      isActive: true,
      url: "/analytics",
      items: [
        {
          title: "銷售報表",
          url: "/analytics/sales",
        },
        {
          title: "庫存報表",
          url: "/analytics/inventory",
        },
        {
          title: "使用者分析",
          url: "/analytics/users",
        },
      ],
    },
    {
      title: "行銷工具",
      icon: TrendingUpIcon,
      url: "/marketing",
      items: [
        {
          title: "優惠券管理",
          url: "/marketing/coupons",
        },
        {
          title: "促銷活動",
          url: "/marketing/campaigns",
        },
        {
          title: "會員積分",
          url: "/marketing/points",
        },
      ],
    },
    {
      title: "內容管理",
      icon: FileTextIcon,
      url: "/content",
      items: [
        {
          title: "頁面管理",
          url: "/content/pages",
        },
        {
          title: "部落格文章",
          url: "/content/blog",
        },
        {
          title: "媒體庫",
          url: "/content/media",
        },
      ],
    },
  ],
  navSecondary: [
    {
      title: "系統設定",
      url: "/settings",
      icon: SettingsIcon,
    },
    {
      title: "技術支援",
      url: "/support",
      icon: HelpCircleIcon,
    },
    {
      title: "搜尋",
      url: "/search",
      icon: SearchIcon,
    },
  ],
  documents: [
    {
      name: "快速統計",
      url: "/quick-stats",
      icon: DatabaseIcon,
    },
    {
      name: "熱銷商品",
      url: "/hot-products",
      icon: TrendingUpIcon,
    },
    {
      name: "今日訂單",
      url: "/today-orders",
      icon: CalendarIcon,
    },
  ],
}

/**
 * LomisX3 應用側邊欄組件
 * 基於 shadcn/ui dashboard-01 官方示例
 * 提供完整的管理系統導覽功能
 */
export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
  return (
    <Sidebar collapsible="offcanvas" {...props}>
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton
              asChild
              className="data-[slot=sidebar-menu-button]:!p-1.5"
            >
              <a href="/">
                <ArrowUpCircleIcon className="h-5 w-5" />
                <span className="text-base font-semibold">LomisX3</span>
              </a>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>
      <SidebarContent>
        <NavMain items={data.navMain} />
        <NavMain items={data.navClouds} />
        <NavDocuments items={data.documents} />
        <NavSecondary items={data.navSecondary} className="mt-auto" />
      </SidebarContent>
      <SidebarFooter>
        <NavUser user={data.user} />
      </SidebarFooter>
    </Sidebar>
  )
}
