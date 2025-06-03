# 商品分類模組監控配置

本目錄包含商品分類模組的監控配置檔案，用於建立完整的可觀測性解決方案。

## 📁 檔案結構

```
docs/monitoring/
├── README.md                               # 本說明文檔
├── grafana-dashboard-product-category.json # Grafana 儀表板配置
└── ../infrastructure/prometheus/alerts.yml  # Prometheus 告警規則
```

## 🎯 監控目標

### 核心指標

1. **快取效能**
   - 快取命中率
   - 快取清除 Job 執行狀況
   - 快取清除時長分布

2. **API 效能**
   - API 回應時間 (P50/P95/P99)
   - API 請求量和錯誤率
   - 端點級別的效能追蹤

3. **系統資源**
   - Redis 記憶體使用率和連線數
   - Laravel Queue 處理狀況
   - 系統負載指標

## 🚀 部署指南

### 1. Grafana 儀表板匯入

```bash
# 方法 1: 使用 Grafana UI
1. 登入 Grafana
2. 點選 "+" -> "Import"
3. 上傳 grafana-dashboard-product-category.json
4. 設定資料源為您的 Prometheus 實例

# 方法 2: 使用 API
curl -X POST \
  http://grafana:3000/api/dashboards/db \
  -H 'Content-Type: application/json' \
  -H 'Authorization: Bearer YOUR_API_TOKEN' \
  -d @grafana-dashboard-product-category.json
```

### 2. Prometheus 告警規則配置

```yaml
# 將以下內容加入您的 prometheus.yml
rule_files:
  - "infrastructure/prometheus/alerts.yml"

# 重新載入 Prometheus 配置
curl -X POST http://prometheus:9090/-/reload
```

### 3. Alertmanager 配置

```yaml
# alertmanager.yml 範例
global:
  smtp_smarthost: 'localhost:587'
  smtp_from: 'alerts@example.com'

route:
  group_by: ['alertname', 'service']
  group_wait: 10s
  group_interval: 10s
  repeat_interval: 1h
  receiver: 'web.hook'

receivers:
- name: 'web.hook'
  email_configs:
  - to: 'admin@example.com'
    subject: '{{ .GroupLabels.service }} 告警: {{ .GroupLabels.alertname }}'
    body: |
      {{ range .Alerts }}
      告警: {{ .Annotations.summary }}
      詳情: {{ .Annotations.description }}
      建議動作: {{ .Annotations.suggested_actions }}
      儀表板: {{ .Annotations.dashboard_url }}
      {{ end }}
```

## 📊 監控指標詳細說明

### 快取指標

| 指標名稱 | 類型 | 說明 |
|---------|------|------|
| `product_category_cache_hits_total` | Counter | 快取命中次數 |
| `product_category_cache_misses_total` | Counter | 快取未命中次數 |
| `cache_flush_job_total` | Counter | 快取清除 Job 執行次數 |
| `cache_flush_job_duration_seconds` | Histogram | 快取清除 Job 執行時長 |

### API 指標

| 指標名稱 | 類型 | 說明 |
|---------|------|------|
| `laravel_http_requests_total` | Counter | HTTP 請求總數 |
| `laravel_http_request_duration_seconds` | Histogram | HTTP 請求處理時間 |

### 系統指標

| 指標名稱 | 類型 | 說明 |
|---------|------|------|
| `redis_memory_used_bytes` | Gauge | Redis 記憶體使用量 |
| `redis_connected_clients` | Gauge | Redis 連線客戶端數 |
| `laravel_queue_size` | Gauge | Queue 中待處理 Job 數量 |
| `laravel_queue_jobs_total` | Counter | Queue Job 處理總數 |

## 🚨 告警規則說明

### P1 (Critical) 告警

- **ProductCategoryCacheFlushJobFailureRateHigh**: Job 失敗率 > 10%
- **ProductCategoryQueueBacklogHigh**: Queue 積壓 > 100 個 Job
- **ProductCategoryAPIErrorRateHigh**: API 錯誤率 > 5%

### P2 (Warning) 告警

- **ProductCategoryCacheHitRateLow**: 快取命中率 < 80%
- **ProductCategoryAPILatencyHigh**: API P95 延遲 > 500ms
- **ProductCategoryCacheFlushJobDurationHigh**: Job 執行時間 > 1s
- **RedisMemoryUsageHigh**: Redis 記憶體使用率 > 85%

## 🔧 故障排除指南

### 快取命中率低

1. **檢查 Redis 狀態**
   ```bash
   redis-cli info memory
   redis-cli info stats
   ```

2. **查看快取清除日誌**
   ```bash
   tail -f storage/logs/laravel.log | grep "cache_flush"
   ```

3. **分析快取鍵分布**
   ```bash
   redis-cli --scan --pattern "product_category:*" | wc -l
   ```

### Job 執行失敗

1. **檢查 Queue Worker 狀態**
   ```bash
   php artisan queue:work --once --verbose
   ```

2. **查看失敗 Job 詳情**
   ```bash
   php artisan queue:failed
   ```

3. **重試失敗的 Job**
   ```bash
   php artisan queue:retry all
   ```

### API 回應時間過長

1. **啟用查詢日誌**
   ```php
   // 在 AppServiceProvider 中
   DB::listen(function ($query) {
       if ($query->time > 100) {
           Log::warning('Slow query detected', [
               'sql' => $query->sql,
               'time' => $query->time
           ]);
       }
   });
   ```

2. **分析慢查詢**
   ```bash
   grep "Slow query" storage/logs/laravel.log
   ```

3. **檢查索引使用情況**
   ```sql
   EXPLAIN SELECT * FROM product_categories WHERE path LIKE '/1/%';
   ```

## 📈 效能最佳化建議

### 快取策略

1. **分層快取**: 實施 L1 (應用記憶體) + L2 (Redis) 快取
2. **智慧預熱**: 根據存取模式預熱常用資料
3. **漸進式過期**: 避免快取雪崩

### 監控最佳實務

1. **設定合理的告警閾值**: 避免過度告警
2. **建立 Runbook**: 為每個告警提供處理指南
3. **定期檢視指標**: 調整閾值和策略

## 🔗 相關連結

- [Grafana 官方文檔](https://grafana.com/docs/)
- [Prometheus 告警規則](https://prometheus.io/docs/prometheus/latest/configuration/alerting_rules/)
- [Laravel 監控最佳實務](https://laravel.com/docs/logging)
- [Redis 監控指南](https://redis.io/topics/admin) 