/**
 * DashboardCharts - 儀表板圖表組件
 * 
 * 提供業務分析圖表展示
 * - 銷售趨勢圖表
 * - 商品銷量排行
 * - 使用者活動統計
 * - 營收分析圖表
 * 
 * @author LomisX3 開發團隊
 * @version 1.0.0
 */

import React, { useMemo } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { 
  TrendingUp, 
  TrendingDown,
  BarChart3, 
  PieChart,
  Activity,
  Package,
  Users,
  Calendar,
  RefreshCw
} from 'lucide-react';
import {
  type ChartConfig 
} from '@/components/ui/chart';



/**
 * 圖表資料介面
 */
interface ChartDataPoint {
  name: string;
  value: number;
  [key: string]: any;
}

/**
 * 圖表配置介面
 */
interface ChartConfiguration {
  title: string;
  description: string;
  type: 'area' | 'bar' | 'line' | 'pie';
  data: ChartDataPoint[];
  config: ChartConfig;
  permission?: string;
  trend?: {
    value: number;
    type: 'increase' | 'decrease';
    period: string;
  };
}

/**
 * 組件屬性
 */
interface DashboardChartsProps {
  /** 自定義 CSS 類別 */
  className?: string;
  /** 圖表佈局 */
  layout?: 'grid-2' | 'grid-3' | 'single';
  /** 是否顯示趨勢 */
  showTrends?: boolean;
  /** 是否顯示操作按鈕 */
  showActions?: boolean;
  /** 時間範圍 */
  timeRange?: '7d' | '30d' | '90d' | '1y';
}

/**
 * 儀表板圖表組件
 */
export const DashboardCharts: React.FC<DashboardChartsProps> = ({
  className = '',
  layout = 'grid-2',
  showTrends = true,
  showActions = true,
  timeRange = '30d'
}) => {
  /**
   * 模擬圖表資料
   */
  const chartData = useMemo(() => {
    return {
      salesData: [
        { name: '1月', value: 85000, trend: 12 },
        { name: '2月', value: 92000, trend: 8 },
        { name: '3月', value: 78000, trend: -15 },
        { name: '4月', value: 95000, trend: 22 },
        { name: '5月', value: 88000, trend: -7 },
        { name: '6月', value: 105000, trend: 19 },
      ],
      productData: [
        { name: 'MC01 鏡櫃', value: 145 },
        { name: 'MC02 浴櫃', value: 118 },
        { name: 'MC03 收納櫃', value: 92 },
        { name: 'MC04 洗手台', value: 78 },
        { name: 'MC05 鏡框', value: 62 },
      ],
      categoryData: [
        { name: '浴室家具', value: 45, color: '#0088FE' },
        { name: '收納用品', value: 30, color: '#00C49F' },
        { name: '裝飾配件', value: 15, color: '#FFBB28' },
        { name: '清潔用品', value: 10, color: '#FF8042' },
      ],
      userActivity: [
        { name: '週一', active: 234, new: 12 },
        { name: '週二', active: 345, new: 18 },
        { name: '週三', active: 287, new: 15 },
        { name: '週四', active: 398, new: 22 },
        { name: '週五', active: 456, new: 28 },
        { name: '週六', active: 512, new: 35 },
        { name: '週日', active: 389, new: 20 },
      ]
    };
  }, [timeRange]);

  /**
   * 取得網格樣式
   */
  const getGridClass = () => {
    switch (layout) {
      case 'grid-3':
        return 'grid gap-6 lg:grid-cols-3';
      case 'single':
        return 'space-y-6';
      case 'grid-2':
      default:
        return 'grid gap-6 lg:grid-cols-2';
    }
  };

  /**
   * 渲染趨勢指標
   */
  const renderTrend = (value: number, period = '較上月') => {
    if (!showTrends) return null;

    const isPositive = value > 0;
    const TrendIcon = isPositive ? TrendingUp : TrendingDown;
    const trendColor = isPositive ? 'text-green-600' : 'text-red-600';

    return (
      <div className={`flex items-center gap-1 text-sm ${trendColor}`}>
        <TrendIcon className="h-3 w-3" />
        <span>{Math.abs(value)}%</span>
        <span className="text-muted-foreground">{period}</span>
      </div>
    );
  };

  return (
    <div className={`space-y-6 ${className}`}>
      {/* 標題和操作區域 */}
      {showActions && (
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-xl font-semibold text-foreground">
              業務分析
            </h2>
            <p className="text-muted-foreground">
              實時業務數據和趨勢分析
            </p>
          </div>
          <div className="flex items-center gap-2">
            <Badge variant="outline" className="gap-1">
              <Activity className="h-3 w-3" />
              即時更新
            </Badge>
            <Button variant="outline" size="sm">
              <Calendar className="h-4 w-4 mr-2" />
              時間範圍
            </Button>
            <Button variant="outline" size="sm">
              匯出報表
            </Button>
          </div>
        </div>
      )}

      {/* 圖表網格 */}
      <div className={getGridClass()}>
        {/* 銷售趨勢圖表 */}
        <Card className="transition-all hover:shadow-md">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <div className="space-y-1">
              <CardTitle className="text-base font-medium flex items-center gap-2">
                <BarChart3 className="h-4 w-4" />
                銷售趨勢
              </CardTitle>
              <CardDescription className="text-sm">過去6個月的營業額變化</CardDescription>
            </div>
            <div className="flex items-center gap-2">
              {renderTrend(12.5)}
              <Button variant="ghost" size="sm">
                <RefreshCw className="h-4 w-4" />
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div className="h-[300px] flex items-center justify-center bg-muted/20 rounded-lg">
              <div className="text-center">
                <BarChart3 className="h-12 w-12 mx-auto mb-2 text-muted-foreground" />
                <p className="text-sm text-muted-foreground">
                  營業額趨勢圖表 (需要圖表庫支援)
                </p>
                <div className="mt-4 grid grid-cols-3 gap-4 text-xs">
                  {chartData.salesData.slice(0, 3).map((item, index) => (
                    <div key={index} className="text-center">
                      <div className="font-medium">{item.name}</div>
                      <div className="text-muted-foreground">${item.value.toLocaleString()}</div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* 熱銷商品排行 */}
        <Card className="transition-all hover:shadow-md">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <div className="space-y-1">
              <CardTitle className="text-base font-medium flex items-center gap-2">
                <Package className="h-4 w-4" />
                熱銷商品排行
              </CardTitle>
              <CardDescription className="text-sm">本月銷量前5名商品</CardDescription>
            </div>
            <div className="flex items-center gap-2">
              {renderTrend(8.3)}
              <Button variant="ghost" size="sm">
                <RefreshCw className="h-4 w-4" />
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {chartData.productData.map((product, index) => (
                <div key={index} className="flex items-center justify-between p-2 rounded-lg hover:bg-muted/50">
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-sm font-medium">
                      {index + 1}
                    </div>
                    <div>
                      <div className="font-medium">{product.name}</div>
                      <div className="text-sm text-muted-foreground">銷量 {product.value} 件</div>
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="w-20 h-2 bg-muted rounded-full overflow-hidden">
                      <div 
                        className="h-full bg-primary transition-all"
                        style={{ width: `${(product.value / 150) * 100}%` }}
                      />
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* 商品分類占比 */}
        <Card className="transition-all hover:shadow-md">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <div className="space-y-1">
              <CardTitle className="text-base font-medium flex items-center gap-2">
                <PieChart className="h-4 w-4" />
                商品分類占比
              </CardTitle>
              <CardDescription className="text-sm">各分類銷售佔比分析</CardDescription>
            </div>
            <Button variant="ghost" size="sm">
              <RefreshCw className="h-4 w-4" />
            </Button>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {chartData.categoryData.map((category, index) => (
                <div key={index} className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div 
                      className="w-3 h-3 rounded-full"
                      style={{ backgroundColor: category.color }}
                    />
                    <span className="text-sm">{category.name}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <div className="w-16 h-2 bg-muted rounded-full overflow-hidden">
                      <div 
                        className="h-full transition-all"
                        style={{ 
                          width: `${category.value}%`,
                          backgroundColor: category.color 
                        }}
                      />
                    </div>
                    <span className="text-sm font-medium min-w-[3rem] text-right">
                      {category.value}%
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>

        {/* 使用者活動 */}
        <Card className="transition-all hover:shadow-md">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <div className="space-y-1">
              <CardTitle className="text-base font-medium flex items-center gap-2">
                <Users className="h-4 w-4" />
                使用者活動
              </CardTitle>
              <CardDescription className="text-sm">本週用戶活躍度統計</CardDescription>
            </div>
            <div className="flex items-center gap-2">
              {renderTrend(15.7, '較上週')}
              <Button variant="ghost" size="sm">
                <RefreshCw className="h-4 w-4" />
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {chartData.userActivity.map((day, index) => (
                <div key={index} className="flex items-center justify-between p-2 rounded-lg hover:bg-muted/50">
                  <div className="font-medium min-w-[3rem]">{day.name}</div>
                  <div className="flex items-center gap-4 flex-1 justify-end">
                    <div className="text-sm text-muted-foreground">
                      活躍: {day.active}
                    </div>
                    <div className="text-sm text-muted-foreground">
                      新增: {day.new}
                    </div>
                    <div className="w-20 h-2 bg-muted rounded-full overflow-hidden">
                      <div 
                        className="h-full bg-blue-500 transition-all"
                        style={{ width: `${(day.active / 600) * 100}%` }}
                      />
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* 圖表說明 */}
      <div className="bg-muted/50 rounded-lg p-4">
        <h3 className="font-medium mb-2 flex items-center gap-2">
          <Activity className="h-4 w-4" />
          數據說明
        </h3>
        <div className="grid gap-2 text-sm text-muted-foreground md:grid-cols-2">
          <div>• 所有數據為即時更新，每5分鐘自動重新整理</div>
          <div>• 圖表支援滑鼠懸停查看詳細數值</div>
          <div>• 營業額數據已扣除退款和折扣</div>
          <div>• 用戶活動數據基於登入記錄統計</div>
        </div>
      </div>
    </div>
  );
};

export default DashboardCharts; 