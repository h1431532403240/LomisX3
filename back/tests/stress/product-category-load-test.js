import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

/**
 * 商品分類模組 K6 負載測試腳本
 * 
 * 測試場景包含：
 * - API 端點性能測試
 * - 併發用戶模擬
 * - 快取效能驗證
 * - 錯誤率監控
 */

// 自定義指標
const errorRate = new Rate('errors');
const customTrend = new Trend('custom_duration');
const apiCounter = new Counter('api_calls');

// 測試配置
export const options = {
  // 階段性負載測試
  stages: [
    { duration: '2m', target: 100 }, // 2分鐘內逐漸增加到100用戶
    { duration: '5m', target: 100 }, // 保持100用戶5分鐘
    { duration: '2m', target: 200 }, // 2分鐘內增加到200用戶  
    { duration: '5m', target: 200 }, // 保持200用戶5分鐘
    { duration: '2m', target: 300 }, // 2分鐘內增加到300用戶
    { duration: '5m', target: 300 }, // 保持300用戶5分鐘（峰值測試）
    { duration: '2m', target: 0 },   // 2分鐘內降到0用戶
  ],

  // 性能閾值設定
  thresholds: {
    // HTTP 請求失敗率應低於 1%
    http_req_failed: ['rate<0.01'],
    
    // 95% 的請求應在 500ms 內完成
    http_req_duration: ['p(95)<500'],
    
    // 平均響應時間應低於 200ms
    http_req_duration: ['avg<200'],
    
    // 自定義錯誤率應低於 5%
    errors: ['rate<0.05'],
    
    // 每秒請求數應大於 100
    http_reqs: ['rate>100'],
  },
};

// 測試環境配置
const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const API_TOKEN = __ENV.API_TOKEN || '';

// API 端點配置
const endpoints = {
  getCategories: `${BASE_URL}/api/product-categories`,
  getCategory: (id) => `${BASE_URL}/api/product-categories/${id}`,
  createCategory: `${BASE_URL}/api/product-categories`,
  updateCategory: (id) => `${BASE_URL}/api/product-categories/${id}`,
  deleteCategory: (id) => `${BASE_URL}/api/product-categories/${id}`,
  getTree: `${BASE_URL}/api/product-categories/tree`,
  getBreadcrumbs: (id) => `${BASE_URL}/api/product-categories/${id}/breadcrumbs`,
  getChildren: (id) => `${BASE_URL}/api/product-categories/${id}/children`,
  searchCategories: `${BASE_URL}/api/product-categories/search`,
  activityLogs: `${BASE_URL}/api/activity-logs`,
};

// HTTP 請求通用頭部
const headers = {
  'Content-Type': 'application/json',
  'Accept': 'application/json',
  ...(API_TOKEN && { 'Authorization': `Bearer ${API_TOKEN}` }),
};

/**
 * 設置函數 - 在每個虛擬用戶開始前執行
 */
export function setup() {
  console.log('🚀 開始負載測試...');
  console.log(`目標服務: ${BASE_URL}`);
  
  // 檢查服務可用性
  const healthCheck = http.get(`${BASE_URL}/api/health`, { headers });
  
  if (healthCheck.status !== 200) {
    console.error('❌ 服務健康檢查失敗');
    return null;
  }
  
  console.log('✅ 服務健康檢查通過');
  
  // 返回測試數據
  return {
    testStartTime: new Date().toISOString(),
    baseUrl: BASE_URL,
  };
}

/**
 * 主要測試函數 - 每個虛擬用戶執行的邏輯
 */
export default function (data) {
  // 模擬不同的用戶行為模式
  const userType = Math.random();
  
  if (userType < 0.4) {
    // 40% 瀏覽型用戶
    browseCategoriesScenario();
  } else if (userType < 0.7) {
    // 30% 搜尋型用戶  
    searchCategoriesScenario();
  } else if (userType < 0.9) {
    // 20% 管理型用戶
    manageCategoriesScenario();
  } else {
    // 10% 重度用戶
    heavyUserScenario();
  }
  
  // 模擬用戶思考時間
  sleep(Math.random() * 3 + 1); // 1-4秒隨機等待
}

/**
 * 瀏覽型用戶場景
 * 主要進行讀取操作
 */
function browseCategoriesScenario() {
  const startTime = new Date();
  
  // 1. 獲取分類樹狀結構
  let response = http.get(endpoints.getTree, { headers });
  apiCounter.add(1);
  
  const treeSuccess = check(response, {
    '分類樹狀結構加載成功': (r) => r.status === 200,
    '分類樹狀結構響應時間 < 300ms': (r) => r.timings.duration < 300,
    '分類樹狀結構有數據': (r) => {
      try {
        const data = JSON.parse(r.body);
        return data.success && data.data && data.data.length > 0;
      } catch {
        return false;
      }
    },
  });
  
  if (!treeSuccess) {
    errorRate.add(1);
  }
  
  // 2. 隨機瀏覽某個分類詳情
  const categoryId = Math.floor(Math.random() * 50) + 1;
  response = http.get(endpoints.getCategory(categoryId), { headers });
  apiCounter.add(1);
  
  const categorySuccess = check(response, {
    '分類詳情加載成功或404': (r) => r.status === 200 || r.status === 404,
    '分類詳情響應時間 < 200ms': (r) => r.timings.duration < 200,
  });
  
  if (!categorySuccess && response.status !== 404) {
    errorRate.add(1);
  }
  
  // 3. 如果分類存在，獲取其麵包屑
  if (response.status === 200) {
    response = http.get(endpoints.getBreadcrumbs(categoryId), { headers });
    apiCounter.add(1);
    
    const breadcrumbsSuccess = check(response, {
      '麵包屑加載成功': (r) => r.status === 200,
      '麵包屑響應時間 < 150ms': (r) => r.timings.duration < 150,
    });
    
    if (!breadcrumbsSuccess) {
      errorRate.add(1);
    }
  }
  
  // 記錄總體響應時間
  customTrend.add(new Date() - startTime);
}

/**
 * 搜尋型用戶場景
 * 主要進行搜尋和篩選操作
 */
function searchCategoriesScenario() {
  const startTime = new Date();
  
  // 1. 執行搜尋
  const searchTerms = ['電子', '服裝', '食品', '書籍', '家具', '分類'];
  const searchTerm = searchTerms[Math.floor(Math.random() * searchTerms.length)];
  
  let response = http.get(
    `${endpoints.searchCategories}?q=${encodeURIComponent(searchTerm)}&per_page=20`,
    { headers }
  );
  apiCounter.add(1);
  
  const searchSuccess = check(response, {
    '搜尋執行成功': (r) => r.status === 200,
    '搜尋響應時間 < 400ms': (r) => r.timings.duration < 400,
    '搜尋有結果': (r) => {
      try {
        const data = JSON.parse(r.body);
        return data.success;
      } catch {
        return false;
      }
    },
  });
  
  if (!searchSuccess) {
    errorRate.add(1);
  }
  
  // 2. 獲取分類列表（帶篩選）
  response = http.get(
    `${endpoints.getCategories}?filter[status]=true&sort=name&per_page=30`,
    { headers }
  );
  apiCounter.add(1);
  
  const listSuccess = check(response, {
    '分類列表加載成功': (r) => r.status === 200,
    '分類列表響應時間 < 300ms': (r) => r.timings.duration < 300,
  });
  
  if (!listSuccess) {
    errorRate.add(1);
  }
  
  customTrend.add(new Date() - startTime);
}

/**
 * 管理型用戶場景
 * 包含 CRUD 操作
 */
function manageCategoriesScenario() {
  const startTime = new Date();
  
  // 1. 創建新分類
  const categoryData = {
    name: `測試分類-${Math.random().toString(36).substr(2, 9)}`,
    status: true,
    description: `K6 負載測試創建的分類 - ${new Date().toISOString()}`,
  };
  
  let response = http.post(
    endpoints.createCategory,
    JSON.stringify(categoryData),
    { headers }
  );
  apiCounter.add(1);
  
  const createSuccess = check(response, {
    '分類創建成功': (r) => r.status === 201,
    '分類創建響應時間 < 500ms': (r) => r.timings.duration < 500,
  });
  
  let categoryId = null;
  if (createSuccess && response.status === 201) {
    try {
      const data = JSON.parse(response.body);
      categoryId = data.data.id;
    } catch (e) {
      errorRate.add(1);
    }
  } else {
    errorRate.add(1);
  }
  
  // 2. 如果創建成功，執行更新操作
  if (categoryId) {
    const updateData = {
      description: `更新的描述 - ${new Date().toISOString()}`,
    };
    
    response = http.put(
      endpoints.updateCategory(categoryId),
      JSON.stringify(updateData),
      { headers }
    );
    apiCounter.add(1);
    
    const updateSuccess = check(response, {
      '分類更新成功': (r) => r.status === 200,
      '分類更新響應時間 < 400ms': (r) => r.timings.duration < 400,
    });
    
    if (!updateSuccess) {
      errorRate.add(1);
    }
    
    // 3. 查看活動日誌
    response = http.get(
      `${endpoints.activityLogs}?filter[subject_id]=${categoryId}&filter[subject_type]=App\\Models\\ProductCategory`,
      { headers }
    );
    apiCounter.add(1);
    
    const logSuccess = check(response, {
      '活動日誌查詢成功': (r) => r.status === 200,
      '活動日誌響應時間 < 300ms': (r) => r.timings.duration < 300,
    });
    
    if (!logSuccess) {
      errorRate.add(1);
    }
  }
  
  customTrend.add(new Date() - startTime);
}

/**
 * 重度用戶場景
 * 執行複雜的操作組合
 */
function heavyUserScenario() {
  const startTime = new Date();
  
  // 1. 並行請求多個資源
  const requests = [
    ['GET', endpoints.getTree],
    ['GET', `${endpoints.getCategories}?per_page=50`],
    ['GET', `${endpoints.activityLogs}?per_page=20`],
  ];
  
  const responses = http.batch(
    requests.map(([method, url]) => ({
      method,
      url,
      headers,
    }))
  );
  
  apiCounter.add(requests.length);
  
  const batchSuccess = check(responses, {
    '批次請求全部成功': (responses) => 
      responses.every(r => r.status >= 200 && r.status < 300),
    '批次請求響應時間 < 600ms': (responses) =>
      responses.every(r => r.timings.duration < 600),
  });
  
  if (!batchSuccess) {
    errorRate.add(1);
  }
  
  // 2. 執行壓力測試的特殊操作
  const parentId = Math.floor(Math.random() * 20) + 1;
  let response = http.get(endpoints.getChildren(parentId), { headers });
  apiCounter.add(1);
  
  const childrenSuccess = check(response, {
    '子分類查詢成功或404': (r) => r.status === 200 || r.status === 404,
    '子分類查詢響應時間 < 250ms': (r) => r.timings.duration < 250,
  });
  
  if (!childrenSuccess && response.status !== 404) {
    errorRate.add(1);
  }
  
  customTrend.add(new Date() - startTime);
}

/**
 * 清理函數 - 測試結束後執行
 */
export function teardown(data) {
  console.log('🏁 負載測試完成');
  
  if (data) {
    console.log(`測試開始時間: ${data.testStartTime}`);
    console.log(`測試結束時間: ${new Date().toISOString()}`);
  }
  
  // 可以在這裡執行清理操作，如刪除測試數據
}

/**
 * 處理摘要報告
 */
export function handleSummary(data) {
  const summary = {
    testDuration: data.state.testRunDurationMs / 1000,
    totalRequests: data.metrics.http_reqs.values.count,
    failedRequests: data.metrics.http_req_failed.values.passes,
    avgResponseTime: data.metrics.http_req_duration.values.avg,
    p95ResponseTime: data.metrics.http_req_duration.values['p(95)'],
    requestsPerSecond: data.metrics.http_reqs.values.rate,
    errorRate: data.metrics.errors ? data.metrics.errors.values.rate * 100 : 0,
    customMetrics: {
      apiCalls: data.metrics.api_calls ? data.metrics.api_calls.values.count : 0,
      customDuration: data.metrics.custom_duration ? data.metrics.custom_duration.values.avg : 0,
    },
  };
  
  // 輸出 JSON 格式報告
  return {
    'summary.json': JSON.stringify(summary, null, 2),
    'stdout': generateTextSummary(summary),
  };
}

/**
 * 生成文字摘要報告
 */
function generateTextSummary(summary) {
  return `
📊 負載測試摘要報告
==================

⏱️  測試時長: ${summary.testDuration.toFixed(2)} 秒
📈 總請求數: ${summary.totalRequests}
❌ 失敗請求: ${summary.failedRequests}
📊 錯誤率: ${summary.errorRate.toFixed(2)}%
⚡ 每秒請求數: ${summary.requestsPerSecond.toFixed(2)}
📍 平均響應時間: ${summary.avgResponseTime.toFixed(2)}ms
📍 95%響應時間: ${summary.p95ResponseTime.toFixed(2)}ms

🎯 自定義指標:
- API 調用次數: ${summary.customMetrics.apiCalls}
- 自定義平均時長: ${summary.customMetrics.customDuration.toFixed(2)}ms

${summary.errorRate < 5 ? '✅ 測試通過' : '❌ 測試失敗 - 錯誤率過高'}
${summary.p95ResponseTime < 500 ? '✅ 響應時間達標' : '❌ 響應時間超標'}
`;
} 