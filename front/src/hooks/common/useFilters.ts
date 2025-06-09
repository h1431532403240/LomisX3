/**
 * 篩選邏輯 Hook
 * 提供完整的篩選狀態管理和操作功能
 */
import { useState, useCallback, useMemo } from 'react';

/**
 * 篩選值類型
 */
export type FilterValue = string | number | boolean | string[] | number[] | null | undefined;

/**
 * 篩選器狀態
 */
export type FilterState = Record<string, FilterValue>;

/**
 * 篩選器配置
 */
export interface FilterConfig<T extends FilterState = FilterState> {
  initialFilters?: Partial<T>;
  resetKeys?: (keyof T)[];
}

/**
 * 篩選器操作介面
 */
export interface FilterActions<T extends FilterState = FilterState> {
  setFilter: <K extends keyof T>(key: K, value: T[K]) => void;
  setFilters: (filters: Partial<T>) => void;
  removeFilter: <K extends keyof T>(key: K) => void;
  clearFilters: () => void;
  resetFilters: () => void;
  hasActiveFilters: boolean;
  getActiveFilters: () => Partial<T>;
  getFilterValue: <K extends keyof T>(key: K) => T[K];
}

/**
 * 篩選器 Hook 返回值
 */
export interface UseFiltersReturn<T extends FilterState = FilterState> {
  filters: T;
  actions: FilterActions<T>;
}

/**
 * 篩選器 Hook
 * @param config 篩選器配置
 * @returns 篩選器狀態和操作方法
 */
export function useFilters<T extends FilterState = FilterState>(
  config: FilterConfig<T> = {}
): UseFiltersReturn<T> {
  const { initialFilters = {} as Partial<T>, resetKeys } = config;

  const [filters, setFiltersState] = useState<T>(() => ({
    ...({} as T),
    ...initialFilters,
  }));

  // 篩選器操作方法
  const actions: FilterActions<T> = useMemo(() => ({
    setFilter: useCallback(<K extends keyof T>(key: K, value: T[K]) => {
      setFiltersState(prev => ({
        ...prev,
        [key]: value,
      }));
    }, []),

    setFilters: useCallback((newFilters: Partial<T>) => {
      setFiltersState(prev => ({
        ...prev,
        ...newFilters,
      }));
    }, []),

    removeFilter: useCallback(<K extends keyof T>(key: K) => {
      setFiltersState(prev => {
        const newFilters = { ...prev };
        delete newFilters[key];
        return newFilters;
      });
    }, []),

    clearFilters: useCallback(() => {
      setFiltersState({} as T);
    }, []),

    resetFilters: useCallback(() => {
      if (resetKeys) {
        setFiltersState(prev => {
          const newFilters = { ...prev };
          resetKeys.forEach(key => {
            delete newFilters[key];
          });
          return {
            ...newFilters,
            ...initialFilters,
          };
        });
      } else {
        setFiltersState({ ...({} as T), ...initialFilters });
      }
    }, [resetKeys, initialFilters]),

    hasActiveFilters: useMemo(() => {
      return Object.keys(filters).length > 0 && 
             Object.values(filters).some(value => 
               value !== null && 
               value !== undefined && 
               value !== '' && 
               !(Array.isArray(value) && value.length === 0)
             );
    }, [filters]),

    getActiveFilters: useCallback(() => {
      const activeFilters: Partial<T> = {};
      
      Object.entries(filters).forEach(([key, value]) => {
        if (value !== null && 
            value !== undefined && 
            value !== '' && 
            !(Array.isArray(value) && value.length === 0)) {
          (activeFilters as any)[key] = value;
        }
      });
      
      return activeFilters;
    }, [filters]),

    getFilterValue: useCallback(<K extends keyof T>(key: K): T[K] => {
      return filters[key];
    }, [filters]),
  }), [filters, resetKeys, initialFilters]);

  return {
    filters,
    actions,
  };
}

/**
 * 搜尋篩選器 Hook
 * 專門用於搜尋功能的簡化版本
 */
export interface SearchFilters extends FilterState {
  search: string;
  status?: "active" | "inactive" | "suspended";
  sort?: "name" | "email" | "created_at";
  order?: "asc" | "desc";
}

export function useSearchFilters(initialSearch = '') {
  return useFilters<SearchFilters>({
    initialFilters: {
      search: initialSearch,
      sort: 'created_at',
      order: 'desc',
    },
  });
} 