import React from 'react';
import { cn } from '@/lib/utils';

/**
 * 響應式斷點類型
 */
export type Breakpoint = 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl';

/**
 * 響應式容器 Props
 */
export interface ResponsiveContainerProps {
  /**
   * 子元件
   */
  children: React.ReactNode;
  /**
   * 最大寬度
   * @default '7xl'
   */
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | '5xl' | '6xl' | '7xl' | 'full';
  /**
   * 內邊距
   * @default 'default'
   */
  padding?: 'none' | 'sm' | 'default' | 'lg' | 'xl';
  /**
   * 是否居中
   * @default true
   */
  centered?: boolean;
  /**
   * 自訂類名
   */
  className?: string;
}

/**
 * 響應式容器組件
 * 提供跨設備的完美佈局支援
 */
export function ResponsiveContainer({
  children,
  maxWidth = '7xl',
  padding = 'default',
  centered = true,
  className,
}: ResponsiveContainerProps) {
  const maxWidthClasses = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
    '4xl': 'max-w-4xl',
    '5xl': 'max-w-5xl',
    '6xl': 'max-w-6xl',
    '7xl': 'max-w-7xl',
    full: 'max-w-full',
  };

  const paddingClasses = {
    none: '',
    sm: 'px-4 py-2',
    default: 'px-4 py-4 sm:px-6 lg:px-8',
    lg: 'px-6 py-6 sm:px-8 lg:px-12',
    xl: 'px-8 py-8 sm:px-12 lg:px-16',
  };

  return (
    <div
      className={cn(
        'w-full',
        maxWidthClasses[maxWidth],
        paddingClasses[padding],
        centered && 'mx-auto',
        className
      )}
    >
      {children}
    </div>
  );
}

/**
 * 響應式格線 Props
 */
export interface ResponsiveGridProps {
  /**
   * 子元件
   */
  children: React.ReactNode;
  /**
   * 各斷點的欄數配置
   */
  columns?: {
    xs?: number;
    sm?: number;
    md?: number;
    lg?: number;
    xl?: number;
    '2xl'?: number;
  };
  /**
   * 間距大小
   * @default 'default'
   */
  gap?: 'none' | 'sm' | 'default' | 'lg' | 'xl';
  /**
   * 自訂類名
   */
  className?: string;
}

/**
 * 響應式格線組件
 * 提供靈活的格線佈局系統
 */
export function ResponsiveGrid({
  children,
  columns = { xs: 1, sm: 2, md: 3, lg: 4 },
  gap = 'default',
  className,
}: ResponsiveGridProps) {
  const gapClasses = {
    none: 'gap-0',
    sm: 'gap-2',
    default: 'gap-4',
    lg: 'gap-6',
    xl: 'gap-8',
  };

  // 生成響應式格線類名
  const getGridColsClass = () => {
    const classes: string[] = [];
    
    if (columns.xs) classes.push(`grid-cols-${columns.xs}`);
    if (columns.sm) classes.push(`sm:grid-cols-${columns.sm}`);
    if (columns.md) classes.push(`md:grid-cols-${columns.md}`);
    if (columns.lg) classes.push(`lg:grid-cols-${columns.lg}`);
    if (columns.xl) classes.push(`xl:grid-cols-${columns.xl}`);
    if (columns['2xl']) classes.push(`2xl:grid-cols-${columns['2xl']}`);
    
    return classes.join(' ');
  };

  return (
    <div
      className={cn(
        'grid',
        getGridColsClass(),
        gapClasses[gap],
        className
      )}
    >
      {children}
    </div>
  );
}

/**
 * 響應式堆疊 Props
 */
export interface ResponsiveStackProps {
  /**
   * 子元件
   */
  children: React.ReactNode;
  /**
   * 堆疊方向
   */
  direction?: {
    xs?: 'vertical' | 'horizontal';
    sm?: 'vertical' | 'horizontal';
    md?: 'vertical' | 'horizontal';
    lg?: 'vertical' | 'horizontal';
    xl?: 'vertical' | 'horizontal';
    '2xl'?: 'vertical' | 'horizontal';
  };
  /**
   * 間距大小
   * @default 'default'
   */
  gap?: 'none' | 'sm' | 'default' | 'lg' | 'xl';
  /**
   * 對齊方式
   */
  align?: 'start' | 'center' | 'end' | 'stretch';
  /**
   * 自訂類名
   */
  className?: string;
}

/**
 * 響應式堆疊組件
 * 提供響應式的垂直或水平堆疊佈局
 */
export function ResponsiveStack({
  children,
  direction = { xs: 'vertical', md: 'horizontal' },
  gap = 'default',
  align = 'start',
  className,
}: ResponsiveStackProps) {
  const gapClasses = {
    none: 'gap-0',
    sm: 'gap-2',
    default: 'gap-4',
    lg: 'gap-6',
    xl: 'gap-8',
  };

  const alignClasses = {
    start: 'items-start',
    center: 'items-center',
    end: 'items-end',
    stretch: 'items-stretch',
  };

  // 生成響應式方向類名
  const getDirectionClasses = () => {
    const classes: string[] = ['flex'];
    
    if (direction.xs === 'vertical') classes.push('flex-col');
    if (direction.xs === 'horizontal') classes.push('flex-row');
    
    if (direction.sm === 'vertical') classes.push('sm:flex-col');
    if (direction.sm === 'horizontal') classes.push('sm:flex-row');
    
    if (direction.md === 'vertical') classes.push('md:flex-col');
    if (direction.md === 'horizontal') classes.push('md:flex-row');
    
    if (direction.lg === 'vertical') classes.push('lg:flex-col');
    if (direction.lg === 'horizontal') classes.push('lg:flex-row');
    
    if (direction.xl === 'vertical') classes.push('xl:flex-col');
    if (direction.xl === 'horizontal') classes.push('xl:flex-row');
    
    if (direction['2xl'] === 'vertical') classes.push('2xl:flex-col');
    if (direction['2xl'] === 'horizontal') classes.push('2xl:flex-row');
    
    return classes.join(' ');
  };

  return (
    <div
      className={cn(
        getDirectionClasses(),
        gapClasses[gap],
        alignClasses[align],
        className
      )}
    >
      {children}
    </div>
  );
}

/**
 * 響應式顯示/隱藏 Props
 */
export interface ResponsiveVisibilityProps {
  /**
   * 子元件
   */
  children: React.ReactNode;
  /**
   * 顯示配置
   */
  show?: {
    xs?: boolean;
    sm?: boolean;
    md?: boolean;
    lg?: boolean;
    xl?: boolean;
    '2xl'?: boolean;
  };
  /**
   * 隱藏配置
   */
  hide?: {
    xs?: boolean;
    sm?: boolean;
    md?: boolean;
    lg?: boolean;
    xl?: boolean;
    '2xl'?: boolean;
  };
  /**
   * 自訂類名
   */
  className?: string;
}

/**
 * 響應式顯示/隱藏組件
 * 根據螢幕尺寸條件性顯示內容
 */
export function ResponsiveVisibility({
  children,
  show,
  hide,
  className,
}: ResponsiveVisibilityProps) {
  const getVisibilityClasses = () => {
    const classes: string[] = [];
    
    // 處理顯示配置
    if (show) {
      if (show.xs === false) classes.push('hidden');
      if (show.xs === true) classes.push('block');
      if (show.sm === false) classes.push('sm:hidden');
      if (show.sm === true) classes.push('sm:block');
      if (show.md === false) classes.push('md:hidden');
      if (show.md === true) classes.push('md:block');
      if (show.lg === false) classes.push('lg:hidden');
      if (show.lg === true) classes.push('lg:block');
      if (show.xl === false) classes.push('xl:hidden');
      if (show.xl === true) classes.push('xl:block');
      if (show['2xl'] === false) classes.push('2xl:hidden');
      if (show['2xl'] === true) classes.push('2xl:block');
    }
    
    // 處理隱藏配置
    if (hide) {
      if (hide.xs === true) classes.push('hidden');
      if (hide.sm === true) classes.push('sm:hidden');
      if (hide.md === true) classes.push('md:hidden');
      if (hide.lg === true) classes.push('lg:hidden');
      if (hide.xl === true) classes.push('xl:hidden');
      if (hide['2xl'] === true) classes.push('2xl:hidden');
    }
    
    return classes.join(' ');
  };

  return (
    <div className={cn(getVisibilityClasses(), className)}>
      {children}
    </div>
  );
}

/**
 * 使用響應式狀態 Hook
 * 提供當前螢幕尺寸的響應式狀態
 */
export function useResponsive() {
  const [breakpoint, setBreakpoint] = React.useState<Breakpoint>('lg');
  const [isMobile, setIsMobile] = React.useState(false);
  const [isTablet, setIsTablet] = React.useState(false);
  const [isDesktop, setIsDesktop] = React.useState(true);

  React.useEffect(() => {
    const checkSize = () => {
      const width = window.innerWidth;
      
      if (width < 640) {
        setBreakpoint('xs');
        setIsMobile(true);
        setIsTablet(false);
        setIsDesktop(false);
      } else if (width < 768) {
        setBreakpoint('sm');
        setIsMobile(false);
        setIsTablet(true);
        setIsDesktop(false);
      } else if (width < 1024) {
        setBreakpoint('md');
        setIsMobile(false);
        setIsTablet(true);
        setIsDesktop(false);
      } else if (width < 1280) {
        setBreakpoint('lg');
        setIsMobile(false);
        setIsTablet(false);
        setIsDesktop(true);
      } else if (width < 1536) {
        setBreakpoint('xl');
        setIsMobile(false);
        setIsTablet(false);
        setIsDesktop(true);
      } else {
        setBreakpoint('2xl');
        setIsMobile(false);
        setIsTablet(false);
        setIsDesktop(true);
      }
    };

    checkSize();
    window.addEventListener('resize', checkSize);
    
    return () => {
      window.removeEventListener('resize', checkSize);
    };
  }, []);

  return {
    breakpoint,
    isMobile,
    isTablet,
    isDesktop,
    isXs: breakpoint === 'xs',
    isSm: breakpoint === 'sm',
    isMd: breakpoint === 'md',
    isLg: breakpoint === 'lg',
    isXl: breakpoint === 'xl',
    is2Xl: breakpoint === '2xl',
  };
} 