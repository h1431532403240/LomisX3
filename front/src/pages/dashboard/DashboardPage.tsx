/**
 * DashboardPage - 儀表板頁面
 * 
 * 企業級管理控制台主頁
 * - 業務概況統計
 * - 即時數據監控
 * - 銷售趨勢圖表
 * - 快速操作入口
 * 
 * @author LomisX3 開發團隊
 * @version 2.0.0
 */

import React from 'react';
import { Badge } from '@/components/ui/badge';
import { 
  Activity
} from 'lucide-react';

// 導入新創建的組件
import { DashboardStats } from '@/components/features/dashboard/DashboardStats';
import { QuickActions } from '@/components/features/dashboard/QuickActions';
import { DashboardCharts } from '@/components/features/dashboard/DashboardCharts';
import { RecentActivity } from '@/components/features/dashboard/RecentActivity';
import { PageHeader } from '@/components/common/breadcrumb';
import { ResponsiveContainer } from '@/components/common/responsive-container';

// 統計卡片、快速操作、圖表和活動記錄現在都使用模組化組件，相關數據已移至各組件內部

/**
 * 儀表板頁面組件
 */
export default function DashboardPage() {
  // 統計資料和快速操作現在由各自的組件管理

  return (
    <ResponsiveContainer maxWidth="7xl" padding="default">
      <div className="space-y-8">
        {/* 頁面標題和麵包屑 */}
        <PageHeader
          title="控制台"
          description="歡迎回來！以下是您的業務概況"
          breadcrumb={{ showHome: false }}
          actions={
            <div className="flex items-center space-x-2">
              <Badge variant="default" className="gap-1">
                <Activity className="h-3 w-3" />
                系統正常
              </Badge>
              <Badge variant="secondary">
                即時更新
              </Badge>
            </div>
          }
        />

      {/* 統計卡片網格區域 - 使用新的 DashboardStats 組件 */}
      <DashboardStats 
        layout="grid-4"
        showTrends={true}
        showDetails={true}
        className="mb-8"
      />

      {/* 快速操作區域 - 使用新的 QuickActions 組件 */}
      <QuickActions 
        layout="grid"
        maxItems={8}
        showTitle={true}
        className="mb-8"
      />

      {/* 圖表區域 - 使用新的 DashboardCharts 組件 */}
      <DashboardCharts 
        layout="grid-2"
        showTrends={true}
        showActions={true}
        timeRange="30d"
        className="mb-8"
      />

      {/* 最近活動 - 使用新的 RecentActivity 組件 */}
      <RecentActivity 
        limit={8}
        showActions={true}
        showFilters={false}
        autoRefresh={true}
      />
    </div>
    </ResponsiveContainer>
  );
} 