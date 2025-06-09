import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { TrendingUp, ShoppingCart, Star, ArrowUpRight, ArrowDownRight, BarChart3, Activity } from 'lucide-react';
import { 
  ChartContainer, 
  ChartTooltip, 
  ChartTooltipContent 
} from '@/components/ui/chart';
import { Area, AreaChart, Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';

/**
 * Dashboard 統計卡片資料介面
 * 基於 shadcn/ui 官方 Card 組件設計標準
 */
interface DashboardStatCard {
  /**
   * 卡片主標題
   */
  title: string;
  /**
   * 卡片描述（用於 CardDescription）
   */
  description: string;
  /**
   * 主要數值
   */
  value: string;
  /**
   * 變化百分比
   */
  change: string;
  /**
   * 變化趨勢
   */
  trend: 'up' | 'down';
  /**
   * 卡片圖標
   */
  icon: React.ComponentType<{ className?: string }>;
}

/**
 * 格式化數字，添加千分位逗號
 * @param num 要格式化的數字
 * @returns 格式化後的字串
 */
const formatNumber = (num: number): string => {
  return num.toLocaleString('zh-TW');
};

/**
 * 模擬圖表資料 - 銷售趨勢
 */
const salesData = [
  { name: '1月', 營業額: 85000, 訂單: 12 },
  { name: '2月', 營業額: 92000, 訂單: 14 },
  { name: '3月', 營業額: 78000, 訂單: 11 },
  { name: '4月', 營業額: 95000, 訂單: 16 },
  { name: '5月', 營業額: 88000, 訂單: 13 },
  { name: '6月', 營業額: 100000, 訂單: 15 },
];

/**
 * 模擬圖表資料 - 商品銷量
 */
const productData = [
  { name: 'MC01 鏡櫃', 銷量: 45 },
  { name: 'MC02 浴櫃', 銷量: 38 },
  { name: 'MC03 收納櫃', 銷量: 32 },
  { name: 'MC04 洗手台', 銷量: 28 },
  { name: 'MC05 鏡框', 銷量: 22 },
];

/**
 * Dashboard 控制台頁面
 * 
 * 功能特色：
 * - 嚴格遵循 shadcn/ui 官方 Card 組件結構
 * - 使用 CardHeader, CardTitle, CardDescription, CardContent 的標準組合
 * - 統一的顏色系統和主題變數
 * - 響應式設計和無障礙友好
 * - 完整的 TypeScript 類型定義
 */
export const Dashboard: React.FC = () => {
  /**
   * Dashboard 統計卡片資料
   * 按照 shadcn/ui 官方範例結構定義
   */
  const statsCards: DashboardStatCard[] = [
    {
      title: '總營業額',
      description: '本月累計收入',
      value: `$${formatNumber(100000)}`,
      change: '+12%',
      trend: 'up',
      icon: TrendingUp
    },
    {
      title: '今日訂單',
      description: '24小時內新訂單',
      value: `${formatNumber(15)}`,
      change: '+20%',
      trend: 'up',
      icon: ShoppingCart
    },
    {
      title: '熱銷商品',
      description: '本週銷量冠軍',
      value: 'MC01鏡櫃',
      change: '45 件',
      trend: 'up',
      icon: Star
    }
  ];

  return (
    <div className="space-y-8">
      {/* 頁面標題區域 - 使用 shadcn/ui 標準樣式 */}
      <div className="border-b pb-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">
              控制台
            </h1>
            <p className="text-muted-foreground mt-2">
              歡迎回來！以下是您的業務概況
            </p>
          </div>
          <div className="flex items-center space-x-2">
            <Badge variant="default" className="gap-1">
              <Activity className="h-3 w-3" />
              系統正常
            </Badge>
            <Badge variant="secondary">
              即時更新
            </Badge>
          </div>
        </div>
      </div>

      {/* 統計卡片網格區域 - 嚴格按照 shadcn/ui 官方結構 */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {statsCards.map((card, index) => {
          const IconComponent = card.icon;
          
          return (
            <Card key={index} className="transition-shadow hover:shadow-md">
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">
                  {card.title}
                </CardTitle>
                <IconComponent className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">
                  {card.value}
                </div>
                <div className="flex items-center pt-1">
                  <div className={`flex items-center text-xs ${
                    card.trend === 'up' 
                      ? 'text-emerald-600 dark:text-emerald-400' 
                      : 'text-red-600 dark:text-red-400'
                  }`}>
                    {card.trend === 'up' ? (
                      <ArrowUpRight className="h-3 w-3 mr-1" />
                    ) : (
                      <ArrowDownRight className="h-3 w-3 mr-1" />
                    )}
                    {card.change}
                  </div>
                  <CardDescription className="ml-2 text-xs">
                    {card.description}
                  </CardDescription>
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>

      {/* 圖表區域 - 使用官方 Card 結構 */}
      <div className="grid gap-4 lg:grid-cols-2">
        {/* 銷售趨勢圖 */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <TrendingUp className="h-4 w-4" />
              銷售趨勢
            </CardTitle>
            <CardDescription>
              過去6個月的營業額變化
            </CardDescription>
          </CardHeader>
          <CardContent>
            <ChartContainer
              config={{
                營業額: {
                  label: "營業額",
                  color: "hsl(var(--chart-1))",
                },
              }}
              className="h-[300px]"
            >
              <AreaChart data={salesData}>
                <defs>
                  <linearGradient id="colorRevenue" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="hsl(var(--chart-1))" stopOpacity={0.3}/>
                    <stop offset="95%" stopColor="hsl(var(--chart-1))" stopOpacity={0}/>
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                <XAxis 
                  dataKey="name" 
                  className="text-muted-foreground"
                  fontSize={12}
                />
                <YAxis 
                  className="text-muted-foreground"
                  fontSize={12}
                />
                <ChartTooltip content={<ChartTooltipContent />} />
                <Area 
                  type="monotone" 
                  dataKey="營業額" 
                  stroke="hsl(var(--chart-1))" 
                  fillOpacity={1} 
                  fill="url(#colorRevenue)"
                  strokeWidth={2}
                />
              </AreaChart>
            </ChartContainer>
          </CardContent>
        </Card>

        {/* 商品銷量圖 */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <BarChart3 className="h-4 w-4" />
              熱銷商品排行
            </CardTitle>
            <CardDescription>
              本週最受歡迎的商品銷量統計
            </CardDescription>
          </CardHeader>
          <CardContent>
            <ChartContainer
              config={{
                銷量: {
                  label: "銷量",
                  color: "hsl(var(--chart-2))",
                },
              }}
              className="h-[300px]"
            >
              <BarChart data={productData}>
                <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                <XAxis 
                  dataKey="name" 
                  className="text-muted-foreground"
                  fontSize={12}
                  tick={{ fontSize: 10 }}
                />
                <YAxis 
                  className="text-muted-foreground"
                  fontSize={12}
                />
                <ChartTooltip content={<ChartTooltipContent />} />
                <Bar 
                  dataKey="銷量" 
                  fill="hsl(var(--chart-2))"
                  radius={[4, 4, 0, 0]}
                />
              </BarChart>
            </ChartContainer>
          </CardContent>
        </Card>
      </div>

      {/* 快速操作區域 - 優化為官方 Button 樣式 */}
      <Card>
        <CardHeader>
          <CardTitle>快速操作</CardTitle>
          <CardDescription>
            常用功能快速入口
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
            <Button variant="outline" className="h-16 flex-col gap-2">
              <ShoppingCart className="h-4 w-4" />
              <span className="text-sm">新增訂單</span>
            </Button>

            <Button variant="outline" className="h-16 flex-col gap-2">
              <TrendingUp className="h-4 w-4" />
              <span className="text-sm">查看報表</span>
            </Button>

            <Button variant="outline" className="h-16 flex-col gap-2">
              <Star className="h-4 w-4" />
              <span className="text-sm">商品管理</span>
            </Button>

            <Button variant="outline" className="h-16 flex-col gap-2">
              <div className="h-4 w-4 bg-primary rounded-sm flex items-center justify-center">
                <span className="text-primary-foreground text-xs font-bold">+</span>
              </div>
              <span className="text-sm">更多功能</span>
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}; 