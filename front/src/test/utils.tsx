/**
 * 測試工具函數
 * 提供 React Query 和組件測試的輔助功能
 */
import type { ReactNode } from 'react';
import React from 'react';
import type { RenderOptions } from '@testing-library/react';
import { render } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { BrowserRouter } from 'react-router-dom';

// 🆕 建立測試專用的 QueryClient
export function createTestQueryClient() {
  return new QueryClient({
    defaultOptions: {
      queries: {
        retry: false, // 測試中不重試
        gcTime: 0, // 立即清理快取
      },
      mutations: {
        retry: false, // 測試中不重試
      },
    },
  });
}

// 🆕 測試 Wrapper 組件
interface TestWrapperProps {
  children: ReactNode;
  queryClient?: QueryClient;
}

function TestWrapper({ children, queryClient }: TestWrapperProps) {
  const client = queryClient || createTestQueryClient();

  return (
    <QueryClientProvider client={client}>
      <BrowserRouter>
        {children}
      </BrowserRouter>
    </QueryClientProvider>
  );
}

// 🆕 自訂 render 函數
interface CustomRenderOptions extends Omit<RenderOptions, 'wrapper'> {
  queryClient?: QueryClient;
}

export function renderWithProviders(
  ui: React.ReactElement,
  options: CustomRenderOptions = {}
) {
  const { queryClient, ...renderOptions } = options;

  return render(ui, {
    wrapper: (props) => <TestWrapper {...props} queryClient={queryClient} />,
    ...renderOptions,
  });
}

// 🆕 等待查詢完成的工具函數
export async function waitForQuery(queryClient: QueryClient, queryKey: any[]) {
  await queryClient.getQueryCache().find({ queryKey })?.fetch();
}

// 🆕 模擬錯誤的工具函數
export function createMockError(status: number, message: string, errors?: Record<string, string[]>) {
  return {
    status,
    message,
    errors,
  };
}

// 🆕 重新匯出 testing-library 工具
export * from '@testing-library/react';
export { default as userEvent } from '@testing-library/user-event'; 