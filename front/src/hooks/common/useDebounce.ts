/**
 * 防抖處理 Hook
 * 避免頻繁的 API 調用和狀態更新
 */
import { useState, useEffect, useCallback, useRef } from 'react';

/**
 * 防抖 Hook
 * @param value 需要防抖的值
 * @param delay 延遲時間（毫秒）
 * @returns 防抖後的值
 */
export function useDebounce<T>(value: T, delay: number): T {
  const [debouncedValue, setDebouncedValue] = useState<T>(value);

  useEffect(() => {
    const handler = setTimeout(() => {
      setDebouncedValue(value);
    }, delay);

    // 清理函數，當 value 或 delay 變化時清除 timeout
    return () => {
      clearTimeout(handler);
    };
  }, [value, delay]);

  return debouncedValue;
}

/**
 * 防抖回調 Hook
 * @param callback 需要防抖的回調函數
 * @param delay 延遲時間（毫秒）
 * @returns 防抖後的回調函數
 */
export function useDebouncedCallback<T extends (...args: any[]) => any>(
  callback: T,
  delay: number
): (...args: Parameters<T>) => void {
  const timeoutRef = useRef<ReturnType<typeof setTimeout>>();

  const debouncedCallback = useCallback(
    (...args: Parameters<T>) => {
      // 清除之前的 timeout
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }

      // 設置新的 timeout
      timeoutRef.current = setTimeout(() => {
        callback(...args);
      }, delay);
    },
    [callback, delay]
  );

  // 清理函數
  useEffect(() => {
    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
    };
  }, []);

  return debouncedCallback;
}

/**
 * 防抖搜尋 Hook
 * 專門用於搜尋功能的防抖處理
 * @param initialValue 初始搜尋值
 * @param delay 延遲時間（毫秒）
 * @returns 搜尋狀態和方法
 */
export function useDebouncedSearch(initialValue = '', delay = 300) {
  const [searchTerm, setSearchTerm] = useState(initialValue);
  const [isSearching, setIsSearching] = useState(false);
  const debouncedSearchTerm = useDebounce(searchTerm, delay);

  // 當搜尋詞變化時，設置搜尋狀態
  useEffect(() => {
    if (searchTerm !== debouncedSearchTerm) {
      setIsSearching(true);
    } else {
      setIsSearching(false);
    }
  }, [searchTerm, debouncedSearchTerm]);

  const setSearch = useCallback((value: string) => {
    setSearchTerm(value);
  }, []);

  const clearSearch = useCallback(() => {
    setSearchTerm('');
  }, []);

  return {
    searchTerm,
    debouncedSearchTerm,
    isSearching,
    setSearch,
    clearSearch,
  };
}

/**
 * 防抖狀態 Hook
 * 提供防抖的狀態更新功能
 * @param initialValue 初始值
 * @param delay 延遲時間（毫秒）
 * @returns 狀態和更新方法
 */
export function useDebouncedState<T>(initialValue: T, delay: number) {
  const [value, setValue] = useState<T>(initialValue);
  const [debouncedValue, setDebouncedValue] = useState<T>(initialValue);
  const [isPending, setIsPending] = useState(false);

  useEffect(() => {
    if (value !== debouncedValue) {
      setIsPending(true);
      const handler = setTimeout(() => {
        setDebouncedValue(value);
        setIsPending(false);
      }, delay);

      return () => {
        clearTimeout(handler);
      };
    }
  }, [value, debouncedValue, delay]);

  const updateValue = useCallback((newValue: T | ((prevValue: T) => T)) => {
    setValue(newValue);
  }, []);

  return {
    value,
    debouncedValue,
    isPending,
    setValue: updateValue,
  };
} 