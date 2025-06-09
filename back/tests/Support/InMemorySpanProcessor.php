<?php

declare(strict_types=1);

namespace Tests\Support;

use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Future\CancellationInterface;
use OpenTelemetry\SDK\Trace\ReadableSpanInterface;
use OpenTelemetry\SDK\Trace\ReadWriteSpanInterface;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;

/**
 * 記憶體內 Span 處理器（用於測試）
 * 
 * 用於在測試環境中收集和驗證 OpenTelemetry spans
 * 支援 OpenTelemetry SDK v1.5+ 的最新介面簽名
 */
class InMemorySpanProcessor implements SpanProcessorInterface
{
    /**
     * 記錄的 spans 陣列
     *
     * @var array<ReadableSpanInterface>
     */
    private array $spans = [];

    /**
     * 是否已關閉
     *
     * @var bool
     */
    private bool $isShutdown = false;

    /**
     * 當 span 開始時呼叫（SDK v1.5+ 介面簽名）
     * 
     * 注意：OpenTelemetry SDK v1.5+ 的參數順序為 (ReadWriteSpanInterface, ContextInterface)
     *
     * @param ReadWriteSpanInterface $span 開始的 span
     * @param ContextInterface $parentContext 父級上下文
     */
    public function onStart(ReadWriteSpanInterface $span, ContextInterface $parentContext): void
    {
        if ($this->isShutdown) {
            return;
        }

        // 記錄 span 開始的時間戳和上下文資訊
        // 在實際應用中，這裡可以記錄額外的開始時間資訊
    }

    /**
     * 當 span 結束時呼叫
     *
     * @param ReadableSpanInterface $span 結束的 span
     */
    public function onEnd(ReadableSpanInterface $span): void
    {
        if ($this->isShutdown) {
            return;
        }

        // 將結束的 span 加入記錄陣列
        $this->spans[] = $span;
    }

    /**
     * 強制清除所有待處理的 spans（SDK v1.5+ 新簽名）
     *
     * @param CancellationInterface|null $cancellation 取消介面，null 表示無限等待
     * @return bool 是否成功清除
     */
    public function forceFlush(?CancellationInterface $cancellation = null): bool
    {
        if ($this->isShutdown) {
            return false;
        }

        // 在記憶體內處理器中，所有 spans 都是立即處理的
        // 所以這裡不需要實際的清除操作
        return true;
    }

    /**
     * 關閉處理器（SDK v1.5+ 新簽名）
     *
     * @param CancellationInterface|null $cancellation 取消介面，null 表示無限等待
     * @return bool 是否成功關閉
     */
    public function shutdown(?CancellationInterface $cancellation = null): bool
    {
        if ($this->isShutdown) {
            return false;
        }

        $this->isShutdown = true;
        
        // 清空所有記錄的 spans
        $this->spans = [];
        
        return true;
    }

    /**
     * 取得所有記錄的 spans
     *
     * @return array<ReadableSpanInterface>
     */
    public function getSpans(): array
    {
        return $this->spans;
    }

    /**
     * 清除所有記錄的 spans（用於測試重置）
     */
    public function clearSpans(): void
    {
        $this->spans = [];
    }

    /**
     * 取得記錄的 spans 數量
     *
     * @return int
     */
    public function getSpanCount(): int
    {
        return count($this->spans);
    }

    /**
     * 檢查是否已關閉
     *
     * @return bool
     */
    public function isShutdown(): bool
    {
        return $this->isShutdown;
    }

    /**
     * 根據名稱尋找 spans
     *
     * @param string $name Span 名稱
     * @return array<ReadableSpanInterface>
     */
    public function findSpansByName(string $name): array
    {
        return array_filter($this->spans, function (ReadableSpanInterface $span) use ($name) {
            return $span->getName() === $name;
        });
    }

    /**
     * 根據屬性尋找 spans
     *
     * @param string $attributeKey 屬性鍵
     * @param mixed $attributeValue 屬性值
     * @return array<ReadableSpanInterface>
     */
    public function findSpansByAttribute(string $attributeKey, $attributeValue): array
    {
        return array_filter($this->spans, function (ReadableSpanInterface $span) use ($attributeKey, $attributeValue) {
            $attributes = $span->getAttributes();
            return $attributes->get($attributeKey) === $attributeValue;
        });
    }

    /**
     * 取得最新的 span
     *
     * @return ReadableSpanInterface|null
     */
    public function getLatestSpan(): ?ReadableSpanInterface
    {
        return end($this->spans) ?: null;
    }

    /**
     * 取得第一個 span
     *
     * @return ReadableSpanInterface|null
     */
    public function getFirstSpan(): ?ReadableSpanInterface
    {
        return $this->spans[0] ?? null;
    }

    /**
     * 驗證是否包含特定名稱的 span
     *
     * @param string $name Span 名稱
     * @return bool
     */
    public function hasSpanWithName(string $name): bool
    {
        return !empty($this->findSpansByName($name));
    }

    /**
     * 驗證是否包含特定屬性的 span
     *
     * @param string $attributeKey 屬性鍵
     * @param mixed $attributeValue 屬性值
     * @return bool
     */
    public function hasSpanWithAttribute(string $attributeKey, $attributeValue): bool
    {
        return !empty($this->findSpansByAttribute($attributeKey, $attributeValue));
    }

    /**
     * 匯出所有 spans 的摘要資訊（用於除錯）
     *
     * @return array
     */
    public function exportSummary(): array
    {
        return array_map(function (ReadableSpanInterface $span) {
            return [
                'name' => $span->getName(),
                'start_time' => $span->getStartTimestamp(),
                'end_time' => $span->getEndTimestamp(),
                'status' => $span->getStatus()->getCode(),
                'attributes' => $span->getAttributes()->toArray(),
                'events_count' => count($span->getEvents()),
            ];
        }, $this->spans);
    }
} 