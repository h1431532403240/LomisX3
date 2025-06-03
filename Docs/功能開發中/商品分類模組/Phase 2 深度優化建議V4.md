############################################
# == Mini-Patch Sprint Prompt – Phase 2.3 ==
############################################
❗ **執行須知** ❗
1. 依優先順序 (P0→P2) 分批提交，確保 CI 綠燈與現有測試不破。
2. Composer 依賴已齊全；如需新套件請在本 Prompt 最上方列出 require 指令。
3. 所有測試必須維持 **≥ 85 %** 覆蓋率，PHPStan Level 5 無新增 Error。
4. 修改路徑以「// ── 新增:」「// ── 修改:」標示；**嚴禁** 生成前端程式碼。

############################################
# P0 — Blocking / Critical
############################################
0. **PHPStan baseline 自動鎖定 (CI)**
   - // ── 修改: .github/workflows/ci.yml  
     ```yaml
     - name: Detect baseline drift
       run: |
         php vendor/bin/phpstan analyse --no-progress --error-format=table \
           | tee /tmp/stan.txt
         CURRENT_ERRORS=$(grep -Eo '[0-9]+ errors?' /tmp/stan.txt | cut -d' ' -f1)
         BASELINE_ERRORS=$(grep -Eo '"totals":\{"errors":[0-9]+' phpstan-baseline.neon | grep -Eo '[0-9]+')
         if [ "$CURRENT_ERRORS" -gt "$BASELINE_ERRORS" ]; then
           echo "::error::PHPStan error count increased ($CURRENT_ERRORS > $BASELINE_ERRORS)"
           exit 1
         fi
     ```

1. **精準快取清除補丁**
   - // ── 修改: app/Services/ProductCategoryCacheService.php  
     - `forgetAffectedTreeParts()` 新增參數 `?int $originalRootId = null`。  
     - 僅 `Cache::tags([TAG])->forget(<shard>)` 對「舊、現」兩個 rootId 執行；失敗時回退至 debounce flush。
   - // ── 修改: app/Observers/ProductCategoryObserver.php  
     - `updated()` 與 `deleted()` 傳遞 `$category->getRootAncestorId()` 與 `$originalRootId`。

2. **覆蓋率保護**
   - // ── 新增: tests/Feature/CacheDebounceTest.php  
     - 模擬 200 ms 內連續呼叫 `forgetAffectedTreeParts()`，斷言只 dispatch 一個 Job。  
   - // ── 新增: tests/Unit/ProductCategoryServiceSlugTest.php  
     - 測試當前 slug 已存在時，`generateUniqueSlug()` 最多 3 次即可獲得唯一 slug。

############################################
# P1 — High-Value
############################################
3. **OpenTelemetry 測試追蹤器**
   - // ── 修改: phpunit.xml  
     ```xml
     <server name="OTEL_SDK_DISABLED" value="true"/>
     ```
   - // ── 新增: tests/Feature/OtelSpanTest.php  
     - 驗證 `ProductCategoryService::getTree()` 會建立 span 且包含 attribute `result=hit|miss`。

4. **Balanced Stress Seeder 強化**
   - // ── 修改: app/Console/Commands/SeedStressProductCategories.php  
     - 新增 `--distribution=balanced` 與 `--chunk=` 參數。  
     - 使用 **BFS** 方式均勻填充至 `--depth` 層，避免孤兒節點。  
     - 乾跑模式輸出 JSON summary (`records`, `depth_stats`)。

5. **Prometheus label 卡片化**
   - // ── 修改: app/Services/ProductCategoryCacheService.php  
     - Histogram/Counter labels 僅保留  
       `type=tree_shard`, `filter=active|all`, `result=hit|miss|error`。  
     - 在 AppServiceProvider 若 `app()->environment('testing')` 時停用收集。

############################################
# P2 — Nice-to-Have
############################################
6. **Grafana JSON Dashboard 自動化**
   - // ── 新增: ops/grafana/product-categories.json  
     - 將 10 塊面板匯出為 `json`; CI 上傳為 artifact。

7. **OpenAPI / Scribe**
   - // ── 新增: .github/workflows/ci.yml ➜ step `scribe_generate`  
     ```yaml
     - run: php artisan scribe:generate && zip -r api-docs.zip public/docs
       - name: Upload Swagger Docs
         uses: actions/upload-artifact@v3
         with:
           name: api-docs
           path: api-docs.zip
           retention-days: 30
     ```

8. **.env.example 補完**
   - // ── 修改: .env.example  
     ```
     CACHE_FLUSH_QUEUE=low
     PROMETHEUS_NAMESPACE=pc
     OTEL_EXPORTER_OTLP_ENDPOINT=http://localhost:4318
     ```

############################################
# 完成標準
############################################
- CI (Pint + PHPStan L5 + PHPUnit 85 % cov) 全綠。
- 新增測試至少 +50 行覆蓋率，總錯誤數 ≤ baseline。
- 快取清除 trace 在檢驗環境 100 % 命中對應 shard。
- README / docs / CHANGELOG 已同步。
############################################

| 疑慮                           | 回應                                                                                                                 |
| ---------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| **現有 baseline = 技術債會不會養蛀蟲？** | 建 baseline 的目的是「封印」已知誤報或暫不修復的問題，先讓 CI 持續綠燈。透過 Sprint 每週 / 每月遞減 baseline 的 KPI（如本紀錄所列 39→34）就能確保技術債逐步消化，而不影響日常開發效率。 |
| **為何 Observer 還要產生 slug？**   | 若您已改為 Service 控制 slug，Observer 的相應程式碼可刪除；Prompt 中已留意兩種寫法（務必二擇一，保持單一職責）。                                            |
| **getRootAncestorId 效能？**    | 若層級 ≥ 6，建議改用一次性查詢 Materialized Path 或 Closure Table；本 Prompt 僅先實作迭代版本，後續可再優化。                                      |
