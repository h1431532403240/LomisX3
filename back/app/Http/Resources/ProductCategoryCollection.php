<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * 商品分類集合資源
 *
 * 提供商品分類列表的 JSON 回應格式，支援：
 * - Cursor Pagination Meta 資訊
 * - 統一的資料結構
 * - next_cursor 和 prev_cursor base64 編碼
 */
class ProductCategoryCollection extends ResourceCollection
{
    /**
     * 將資源集合轉換為陣列格式
     *
     * @param Request $request HTTP 請求物件
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    /**
     * 取得附加的 Meta 資料
     * 包含 Cursor Pagination 的詳細資訊
     *
     * @param Request $request HTTP 請求物件
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        $meta = [
            'success' => true,
            'message' => '查詢成功',
        ];

        // 如果是 Cursor Pagination，添加相關 Meta 資訊
        if ($this->resource instanceof \Illuminate\Pagination\CursorPaginator) {
            $meta['pagination'] = $this->getCursorPaginationMeta();
        }
        // 如果是一般分頁，添加標準分頁資訊
        elseif ($this->resource instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $meta['pagination'] = $this->getStandardPaginationMeta();
        }

        return $meta;
    }

    /**
     * 取得 Cursor Pagination Meta 資訊
     *
     * @return array<string, mixed>
     */
    private function getCursorPaginationMeta(): array
    {
        $paginator = $this->resource;

        return [
            'type' => 'cursor',
            'per_page' => $paginator->perPage(),
            'has_more_pages' => $paginator->hasMorePages(),
            'has_previous_pages' => ! is_null($paginator->previousCursor()),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'prev_cursor' => $paginator->previousCursor()?->encode(),
            'path' => $paginator->path(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
        ];
    }

    /**
     * 取得標準分頁 Meta 資訊
     *
     * @return array<string, mixed>
     */
    private function getStandardPaginationMeta(): array
    {
        $paginator = $this->resource;

        return [
            'type' => 'standard',
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'has_more_pages' => $paginator->hasMorePages(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'path' => $paginator->path(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'first_page_url' => $paginator->url(1),
            'last_page_url' => $paginator->url($paginator->lastPage()),
        ];
    }

    /**
     * 自訂回應的 Meta 資料
     *
     * @param array<string, mixed> $data 額外資料
     * @return static
     */
    public function additional(array $data): static
    {
        $this->additional = array_merge($this->additional ?? [], $data);
        return $this;
    }

    /**
     * 建立成功回應
     *
     * @param mixed  $data    資料
     * @param string $message 訊息
     * @return array<string, mixed>
     */
    public static function success($data, string $message = '查詢成功'): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * 建立錯誤回應
     *
     * @param string $message 錯誤訊息
     * @param array<string, mixed> $errors 詳細錯誤
     * @param string $code 錯誤代碼
     * @return array<string, mixed>
     */
    public static function error(string $message, array $errors = [], string $code = 'ERROR'): array
    {
        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'code' => $code,
        ];
    }
}
