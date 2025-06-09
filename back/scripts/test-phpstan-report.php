<?php

/**
 * PHPStan 債務分析報告生成測試腳本
 * 
 * 此腳本模擬 GitHub Actions 中的報告生成邏輯
 * 用於驗證報告生成功能是否正常運作
 */

require_once __DIR__ . '/../vendor/autoload.php';

$jsonPath = 'storage/app/phpstan-reports/debt-analysis.json';
$txtPath = 'storage/app/phpstan-reports/debt-analysis.txt';
$reportPath = 'storage/app/phpstan-reports/weekly-report.md';

$currentTime = date('Y-m-d H:i:s T');
$currentBaseline = file_exists('phpstan-baseline.neon') ? file_get_contents('phpstan-baseline.neon') : '';
$baselineErrorCount = substr_count($currentBaseline, '- message:');

// 模擬 PHPStan 分析結果
$simulatedData = [
    'totals' => [
        'errors' => 45,
        'file_errors' => 12
    ],
    'files' => [
        'app/Models/ProductCategory.php' => [
            'messages' => [
                ['identifier' => 'missingType.iterableValue', 'message' => 'Missing type hint'],
                ['identifier' => 'missingType.return', 'message' => 'Missing return type'],
            ]
        ],
        'app/Services/ProductCategoryCacheService.php' => [
            'messages' => [
                ['identifier' => 'method.unused', 'message' => 'Unused method'],
            ]
        ]
    ]
];

$totalErrors = $simulatedData['totals']['errors'];
$totalFileErrors = $simulatedData['totals']['file_errors'];
$files = $simulatedData['files'];

// 統計錯誤類型
$errorTypes = [];
$errorFiles = [];
foreach ($files as $file => $fileData) {
    if (!empty($fileData['messages'])) {
        $errorFiles[$file] = count($fileData['messages']);
        foreach ($fileData['messages'] as $message) {
            $identifier = $message['identifier'] ?? 'unknown';
            $errorTypes[$identifier] = ($errorTypes[$identifier] ?? 0) + 1;
        }
    }
}

// 生成 Markdown 報告
$report = "# 📊 PHPStan 技術債務週報

**報告時間**: {$currentTime}
**分析範圍**: 全專案（無 baseline 過濾）

## 📈 債務總覽

| 指標 | 數量 | 說明 |
|------|------|------|
| 🔴 **總錯誤數** | {$totalErrors} | 當前專案中的所有 PHPStan 錯誤 |
| 📁 **影響檔案數** | {$totalFileErrors} | 包含錯誤的檔案數量 |
| ⚪ **基線錯誤數** | {$baselineErrorCount} | 目前基線中被忽略的錯誤數 |
| 📊 **債務增減** | " . ($totalErrors - $baselineErrorCount) . " | 相對於基線的錯誤變化 |

";

if ($totalErrors > 0) {
    $report .= "
## 🔍 錯誤類型分布

";
    arsort($errorTypes);
    foreach (array_slice($errorTypes, 0, 10) as $type => $count) {
        $report .= "- **{$type}**: {$count} 個錯誤\n";
    }
    
    $report .= "
## 📁 主要問題檔案

";
    arsort($errorFiles);
    foreach (array_slice($errorFiles, 0, 15) as $file => $count) {
        $shortFile = str_replace(getcwd() . '/', '', $file);
        $report .= "- `{$shortFile}`: {$count} 個錯誤\n";
    }
}

$report .= "
## 📋 建議行動項目

";

if ($totalErrors <= $baselineErrorCount) {
    $report .= "✅ **太棒了！** 沒有新的 PHPStan 錯誤產生。建議：
- 從基線中選擇 5-10 個簡單錯誤進行修復
- 更新基線檔案，減少技術債務
";
} else {
    $newErrors = $totalErrors - $baselineErrorCount;
    $report .= "⚠️ **注意！** 發現 {$newErrors} 個新的 PHPStan 錯誤。建議：
- 🔥 **優先處理新錯誤**，避免債務持續累積
- 📝 檢視最近的程式碼變更，找出錯誤根源
- 🔧 修復後，考慮加強 pre-commit hooks
";
}

$report .= "
## 🎯 本週清理目標

根據 **遞減策略**，本週建議：
1. 修復 **5-10 個** 基線錯誤（優先選擇簡單類型）
2. 更新 `phpstan-baseline.neon` 檔案
3. 在 `CHANGELOG.md` 中記錄技術債清理進度
4. 考慮針對高頻錯誤類型制定修復指南

---

> 💡 **提示**: 可使用 `composer phpstan:baseline-update` 更新基線檔案
> 
> 🔗 **相關連結**: [PHPStan 設定檔](./phpstan.neon.dist) | [基線檔案](./phpstan-baseline.neon)
";

// 儲存報告
file_put_contents($reportPath, $report);

// 顯示結果
echo "✅ PHPStan 債務分析報告生成測試完成！\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 統計資訊：\n";
echo "   • 總錯誤數：{$totalErrors}\n";
echo "   • 基線錯誤數：{$baselineErrorCount}\n";
echo "   • 新錯誤數：" . ($totalErrors - $baselineErrorCount) . "\n";
echo "   • 影響檔案數：{$totalFileErrors}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📁 生成檔案：\n";
echo "   • 報告：{$reportPath}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// 顯示錯誤類型統計
if (!empty($errorTypes)) {
    echo "🔍 錯誤類型分布：\n";
    foreach ($errorTypes as $type => $count) {
        echo "   • {$type}: {$count} 個\n";
    }
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
}

echo "💡 提示：可以檢查生成的報告檔案查看詳細內容\n"; 