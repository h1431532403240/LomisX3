# LomisX3 主題系統文檔

## 概述

LomisX3 系統使用 shadcn/ui 標準主題系統，支援淺色和深色主題的無縫切換。

## 主題架構

### CSS 變數系統

主題系統基於 CSS 自訂屬性（CSS Variables），使用 HSL 色彩空間定義：

```css
:root {
  --background: 0 0% 100%;     /* 主背景色 */
  --foreground: 0 0% 3.9%;     /* 主文字色 */
  --primary: 0 0% 9%;          /* 主要色彩 */
  --secondary: 0 0% 96.1%;     /* 次要色彩 */
  /* ... 更多變數 */
}
```

### 主題模式

#### 淺色主題 (Light Mode)
- **背景**: 純白色 `0 0% 100%`
- **文字**: 深灰色 `0 0% 3.9%`
- **主要色**: 深黑色 `0 0% 9%`
- **邊框**: 淺灰色 `0 0% 89.8%`

#### 深色主題 (Dark Mode)
- **背景**: 深灰色 `0 0% 3.9%`
- **文字**: 淺白色 `0 0% 98%`
- **主要色**: 淺白色 `0 0% 98%`
- **邊框**: 深灰色 `0 0% 14.9%`

## 色彩變數完整列表

### 基礎色彩
| 變數名稱 | 淺色模式 | 深色模式 | 用途 |
|---------|---------|---------|------|
| `--background` | `0 0% 100%` | `0 0% 3.9%` | 主背景 |
| `--foreground` | `0 0% 3.9%` | `0 0% 98%` | 主文字 |
| `--primary` | `0 0% 9%` | `0 0% 98%` | 主要按鈕、連結 |
| `--primary-foreground` | `0 0% 98%` | `0 0% 9%` | 主要元素文字 |
| `--secondary` | `0 0% 96.1%` | `0 0% 14.9%` | 次要背景 |
| `--secondary-foreground` | `0 0% 9%` | `0 0% 98%` | 次要元素文字 |

### 功能色彩
| 變數名稱 | 淺色模式 | 深色模式 | 用途 |
|---------|---------|---------|------|
| `--muted` | `0 0% 96.1%` | `0 0% 14.9%` | 靜音背景 |
| `--muted-foreground` | `0 0% 45.1%` | `0 0% 63.9%` | 靜音文字 |
| `--accent` | `0 0% 96.1%` | `0 0% 14.9%` | 強調背景 |
| `--accent-foreground` | `0 0% 9%` | `0 0% 98%` | 強調文字 |
| `--destructive` | `0 84.2% 60.2%` | `0 62.8% 30.6%` | 危險操作 |
| `--destructive-foreground` | `0 0% 98%` | `0 0% 98%` | 危險操作文字 |

### 介面元素
| 變數名稱 | 淺色模式 | 深色模式 | 用途 |
|---------|---------|---------|------|
| `--border` | `0 0% 89.8%` | `0 0% 14.9%` | 邊框 |
| `--input` | `0 0% 89.8%` | `0 0% 14.9%` | 輸入框背景 |
| `--ring` | `0 0% 3.9%` | `0 0% 83.1%` | 焦點環 |
| `--radius` | `0.65rem` | `0.65rem` | 圓角半徑 |

### 側邊欄專用色彩
| 變數名稱 | 淺色模式 | 深色模式 | 用途 |
|---------|---------|---------|------|
| `--sidebar-background` | `0 0% 98%` | `240 5.9% 10%` | 側邊欄背景 |
| `--sidebar-foreground` | `240 5.3% 26.1%` | `240 4.8% 95.9%` | 側邊欄文字 |
| `--sidebar-primary` | `240 5.9% 10%` | `224.3 76.3% 48%` | 側邊欄主要色 |
| `--sidebar-accent` | `240 4.8% 95.9%` | `240 3.7% 15.9%` | 側邊欄強調色 |
| `--sidebar-border` | `220 13% 91%` | `240 3.7% 15.9%` | 側邊欄邊框 |

### 圖表色彩
| 變數名稱 | 淺色模式 | 深色模式 | 用途 |
|---------|---------|---------|------|
| `--chart-1` | `12 76% 61%` | `220 70% 50%` | 圖表色彩 1 |
| `--chart-2` | `173 58% 39%` | `160 60% 45%` | 圖表色彩 2 |
| `--chart-3` | `197 37% 24%` | `30 80% 55%` | 圖表色彩 3 |
| `--chart-4` | `43 74% 66%` | `280 65% 60%` | 圖表色彩 4 |
| `--chart-5` | `27 87% 67%` | `340 75% 55%` | 圖表色彩 5 |

## 主題切換實作

### ModeToggle 組件

```typescript
// 簡化的主題切換按鈕
export function ModeToggle() {
  const { theme, setTheme } = useTheme()

  const toggleTheme = () => {
    setTheme(theme === "dark" ? "light" : "dark")
  }

  return (
    <Button onClick={toggleTheme}>
      <Sun className="dark:hidden" />
      <Moon className="hidden dark:block" />
    </Button>
  )
}
```

### ThemeProvider 配置

```typescript
<ThemeProvider
  attribute="class"
  defaultTheme="light"
  enableSystem={false}
  disableTransitionOnChange
>
  {children}
</ThemeProvider>
```

## 使用方式

### 在組件中使用主題色彩

```typescript
// 使用 Tailwind CSS 類別
<div className="bg-background text-foreground">
  <button className="bg-primary text-primary-foreground">
    主要按鈕
  </button>
</div>

// 使用 CSS 變數
<div style={{ 
  backgroundColor: 'hsl(var(--background))',
  color: 'hsl(var(--foreground))'
}}>
  內容
</div>
```

### 響應式主題

```css
/* 自動響應主題變化 */
.my-component {
  background-color: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  color: hsl(var(--card-foreground));
}
```

## 自訂主題

### 修改色彩變數

如需自訂主題色彩，修改 `src/index.css` 中的 CSS 變數：

```css
:root {
  /* 自訂主要色彩為藍色 */
  --primary: 221.2 83.2% 53.3%;
  --primary-foreground: 210 40% 98%;
}

.dark {
  /* 深色模式的藍色 */
  --primary: 217.2 91.2% 59.8%;
  --primary-foreground: 222.2 84% 4.9%;
}
```

### 新增自訂色彩

```css
:root {
  /* 新增品牌色彩 */
  --brand: 142.1 76.2% 36.3%;
  --brand-foreground: 355.7 100% 97.3%;
}

.dark {
  --brand: 142.1 70.6% 45.3%;
  --brand-foreground: 144.9 80.4% 10%;
}
```

然後在 `tailwind.config.js` 中註冊：

```javascript
module.exports = {
  theme: {
    extend: {
      colors: {
        brand: {
          DEFAULT: "hsl(var(--brand))",
          foreground: "hsl(var(--brand-foreground))",
        },
      },
    },
  },
}
```

## 最佳實踐

### 1. 一致性
- 始終使用定義的 CSS 變數
- 避免硬編碼色彩值
- 遵循 shadcn/ui 的命名慣例

### 2. 可訪問性
- 確保足夠的對比度
- 測試兩種主題模式
- 支援系統偏好設定

### 3. 性能
- 使用 CSS 變數而非 JavaScript 切換
- 避免不必要的重新渲染
- 利用 CSS 轉場效果

## 故障排除

### 常見問題

1. **主題切換不生效**
   - 檢查 ThemeProvider 是否正確包裹應用
   - 確認 CSS 變數已正確定義

2. **色彩顯示異常**
   - 驗證 HSL 值格式是否正確
   - 檢查 Tailwind 配置是否同步

3. **深色模式樣式問題**
   - 確保 `.dark` 選擇器優先級
   - 檢查 CSS 變數覆蓋順序

## 總結

LomisX3 的主題系統提供了完整的淺色/深色模式支援，基於 shadcn/ui 標準實作，具備良好的可維護性和擴展性。透過 CSS 變數系統，可以輕鬆實現主題切換和自訂色彩方案。 