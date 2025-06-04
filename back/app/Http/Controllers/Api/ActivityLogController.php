<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\ActivityLogCollection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * 活動日誌 API 控制器
 * 
 * 提供完整的活動日誌查詢、篩選和統計功能
 * 支援多維度查詢和效能優化
 * 
 * @group 統計與監控
 */
class ActivityLogController extends Controller
{
    /**
     * 取得活動日誌清單
     * 
     * 支援多種篩選條件和排序選項的活動日誌查詢介面
     * 
     * @group 統計與監控
     * 
     * @queryParam filter[log_name] string 日誌名稱篩選 Example: product_categories
     * @queryParam filter[description] string 描述關鍵字搜尋 Example: 建立了新的商品分類
     * @queryParam filter[event] string 事件類型篩選 Example: created
     * @queryParam filter[causer_id] integer 執行者ID篩選 Example: 1
     * @queryParam filter[causer_type] string 執行者類型篩選 Example: App\Models\User
     * @queryParam filter[subject_id] integer 主體ID篩選 Example: 15
     * @queryParam filter[subject_type] string 主體類型篩選 Example: App\Models\ProductCategory
     * @queryParam filter[created_after] string 創建時間起始篩選 Example: 2025-01-01
     * @queryParam filter[created_before] string 創建時間結束篩選 Example: 2025-01-31
     * @queryParam sort string 排序欄位 Example: -created_at
     * @queryParam per_page integer 每頁項目數（1-100） Example: 20
     * @queryParam page integer 頁碼 Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "items": [
     *       {
     *         "id": 123,
     *         "log_name": "product_categories",
     *         "description": "【智慧型手機】建立了新的商品分類",
     *         "event": "created",
     *         "batch_uuid": null,
     *         "causer": {
     *           "id": 1,
     *           "type": "App\\Models\\User",
     *           "name": "管理員",
     *           "email": "admin@example.com"
     *         },
     *         "subject": {
     *           "id": 15,
     *           "type": "App\\Models\\ProductCategory",
     *           "name": "智慧型手機",
     *           "slug": "smartphones",
     *           "status": true,
     *           "depth": 1,
     *           "parent_id": 1
     *         },
     *         "properties": {
     *           "attributes": {
     *             "name": "智慧型手機",
     *             "status": true
     *           },
     *           "formatted_changes": {
     *             "name": {
     *               "new": "智慧型手機",
     *               "old": null,
     *               "changed": true
     *             }
     *           },
     *           "category_info": {
     *             "level": 1,
     *             "is_root": false,
     *             "has_children": false,
     *             "full_path": "電子產品 > 智慧型手機"
     *           }
     *         },
     *         "created_at": "2025-01-07T10:30:00",
     *         "updated_at": "2025-01-07T10:30:00",
     *         "created_at_diff": "2小時前",
     *         "context": {
     *           "has_changes": true,
     *           "is_batch": false,
     *           "subject_exists": true,
     *           "causer_exists": true
     *         }
     *       }
     *     ],
     *     "summary": {
     *       "events_count": {
     *         "created": 45,
     *         "updated": 32,
     *         "deleted": 8
     *       },
     *       "log_names_count": {
     *         "product_categories": 85
     *       },
     *       "causers_count": 5,
     *       "date_range": {
     *         "earliest": "2025-01-01T00:00:00",
     *         "latest": "2025-01-07T12:00:00"
     *       },
     *       "has_batch_operations": true
     *     }
     *   },
     *   "message": "活動日誌清單取得成功",
     *   "meta": {
     *     "total": 85,
     *     "per_page": 20,
     *     "current_page": 1,
     *     "last_page": 5
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $activities = QueryBuilder::for(Activity::class)
            ->allowedFilters([
                'log_name',
                AllowedFilter::partial('description'),
                AllowedFilter::exact('event'),
                AllowedFilter::exact('causer_id'),
                AllowedFilter::exact('causer_type'),
                AllowedFilter::exact('subject_id'),
                AllowedFilter::exact('subject_type'),
                AllowedFilter::scope('created_after'),
                AllowedFilter::scope('created_before'),
            ])
            ->allowedSorts([
                'created_at',
                'updated_at',
                'event',
                'log_name',
                AllowedSort::custom('causer_name', new \App\Sorts\CauserNameSort()),
            ])
            ->with(['causer', 'subject'])
            ->defaultSort('-created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => new ActivityLogCollection($activities),
            'message' => '活動日誌清單取得成功',
            'meta' => [
                'total' => $activities->total(),
                'per_page' => $activities->perPage(),
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
            ]
        ]);
    }

    /**
     * 取得單一活動日誌詳情
     * 
     * @param int $id 活動日誌 ID
     * @return JsonResponse 活動日誌詳細資訊
     */
    public function show(int $id): JsonResponse
    {
        $activity = Activity::with(['causer', 'subject'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ActivityLogResource($activity),
            'message' => '活動日誌詳情取得成功'
        ]);
    }

    /**
     * 取得特定商品分類的活動日誌
     * 
     * @param Request $request HTTP 請求
     * @param int $categoryId 商品分類 ID
     * @return JsonResponse 該分類的活動日誌清單
     */
    public function getCategoryActivities(Request $request, int $categoryId): JsonResponse
    {
        $activities = QueryBuilder::for(Activity::class)
            ->where('subject_type', 'App\Models\ProductCategory')
            ->where('subject_id', $categoryId)
            ->allowedFilters([
                'log_name',
                AllowedFilter::partial('description'),
                AllowedFilter::exact('event'),
                AllowedFilter::exact('causer_id'),
            ])
            ->allowedSorts(['created_at', 'event'])
            ->with(['causer', 'subject'])
            ->defaultSort('-created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => new ActivityLogCollection($activities),
            'message' => '商品分類活動日誌取得成功',
            'meta' => [
                'category_id' => $categoryId,
                'total' => $activities->total(),
                'per_page' => $activities->perPage(),
                'current_page' => $activities->currentPage(),
            ]
        ]);
    }

    /**
     * 取得活動日誌統計資訊
     * 
     * @param Request $request HTTP 請求
     * @return JsonResponse 活動日誌統計數據
     */
    public function statistics(Request $request): JsonResponse
    {
        // 驗證時間範圍參數
        $request->validate([
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'days' => 'sometimes|integer|min:1|max:90'
        ]);

        $query = Activity::query();

        // 應用時間範圍篩選
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date,
                $request->end_date
            ]);
        } elseif ($request->has('days')) {
            $query->where('created_at', '>=', now()->subDays($request->days));
        } else {
            // 預設最近 30 天
            $query->where('created_at', '>=', now()->subDays(30));
        }

        $statistics = [
            'total_activities' => $query->count(),
            'activities_by_event' => $query->groupBy('event')
                ->selectRaw('event, count(*) as count')
                ->pluck('count', 'event'),
            'activities_by_log_name' => $query->groupBy('log_name')
                ->selectRaw('log_name, count(*) as count')
                ->pluck('count', 'log_name'),
            'activities_by_day' => $query->selectRaw('DATE(created_at) as date, count(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date'),
            'top_causers' => $query->whereNotNull('causer_id')
                ->with('causer:id,name')
                ->get()
                ->groupBy('causer_id')
                ->map(function ($activities) {
                    $first = $activities->first();
                    return [
                        'user_id' => $first->causer_id,
                        'user_name' => $first->causer->name ?? '未知用戶',
                        'activity_count' => $activities->count()
                    ];
                })
                ->sortByDesc('activity_count')
                ->take(10)
                ->values(),
            'recent_activities' => Activity::with(['causer:id,name', 'subject'])
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'description' => $activity->description,
                        'event' => $activity->event,
                        'causer_name' => $activity->causer->name ?? '系統',
                        'created_at' => $activity->created_at->format('Y-m-d H:i:s'),
                    ];
                })
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics,
            'message' => '活動日誌統計資訊取得成功'
        ]);
    }

    /**
     * 清理舊的活動日誌
     * 
     * @param Request $request HTTP 請求
     * @return JsonResponse 清理結果
     */
    public function cleanup(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'required|integer|min:30|max:365',
            'dry_run' => 'sometimes|boolean'
        ]);

        $cutoffDate = now()->subDays($request->days);
        $query = Activity::where('created_at', '<', $cutoffDate);
        
        $count = $query->count();
        
        if (!$request->boolean('dry_run', false)) {
            $deleted = $query->delete();
            
            // 記錄清理活動
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'deleted_count' => $deleted,
                    'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s'),
                    'days_threshold' => $request->days
                ])
                ->log('執行了活動日誌清理操作');
                
            return response()->json([
                'success' => true,
                'data' => [
                    'deleted_count' => $deleted,
                    'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s')
                ],
                'message' => "成功清理了 {$deleted} 筆舊的活動日誌"
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'estimated_count' => $count,
                'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s'),
                'dry_run' => true
            ],
            'message' => "預估將清理 {$count} 筆活動日誌（僅為模擬執行）"
        ]);
    }
} 