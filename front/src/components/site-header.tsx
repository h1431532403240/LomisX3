import { Separator } from "@/components/ui/separator"
import { SidebarTrigger } from "@/components/ui/sidebar"
import { ModeToggle } from "@/components/theme"

/**
 * LomisX3 系統頂部導覽列
 * 基於 shadcn/ui dashboard-01 官方示例
 * 提供固定頂部導覽，包含側邊欄切換和主題切換
 */
export function SiteHeader() {
  return (
    <header className="group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 flex h-12 shrink-0 items-center gap-2 border-b transition-[width,height] ease-linear">
      <div className="flex w-full items-center gap-1 px-4 lg:gap-2 lg:px-6">
        <SidebarTrigger className="-ml-1" />
        <Separator
          orientation="vertical"
          className="mx-2 data-[orientation=vertical]:h-4"
        />
        <h1 className="text-base font-medium">LomisX3 管理系統</h1>
        
        {/* 主題切換按鈕 */}
        <div className="ml-auto">
          <ModeToggle />
        </div>
      </div>
    </header>
  )
}
