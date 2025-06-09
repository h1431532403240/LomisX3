/**
 * Button 組件單元測試
 * 
 * 測試 shadcn/ui Button 組件的基本功能
 */

import { render, screen, fireEvent } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import { Button } from '../button';

describe('Button 組件', () => {
  it('應該正確渲染按鈕文字', () => {
    render(<Button>測試按鈕</Button>);
    
    expect(screen.getByRole('button')).toBeInTheDocument();
    expect(screen.getByText('測試按鈕')).toBeInTheDocument();
  });
  
  it('應該正確處理點擊事件', () => {
    const handleClick = vi.fn();
    render(<Button onClick={handleClick}>點擊我</Button>);
    
    const button = screen.getByRole('button');
    fireEvent.click(button);
    
    expect(handleClick).toHaveBeenCalledTimes(1);
  });
  
  it('應該正確應用 disabled 狀態', () => {
    render(<Button disabled>禁用按鈕</Button>);
    
    const button = screen.getByRole('button');
    expect(button).toBeDisabled();
  });
  
  it('應該正確應用不同的變體樣式', () => {
    const { rerender } = render(<Button variant="default">預設按鈕</Button>);
    expect(screen.getByRole('button')).toHaveClass('bg-primary');
    
    rerender(<Button variant="destructive">危險按鈕</Button>);
    expect(screen.getByRole('button')).toHaveClass('bg-destructive');
    
    rerender(<Button variant="outline">外框按鈕</Button>);
    expect(screen.getByRole('button')).toHaveClass('border-input');
    
    rerender(<Button variant="secondary">次要按鈕</Button>);
    expect(screen.getByRole('button')).toHaveClass('bg-secondary');
    
    rerender(<Button variant="ghost">幽靈按鈕</Button>);
    expect(screen.getByRole('button')).toHaveClass('hover:bg-accent');
  });
  
  it('應該正確應用不同的尺寸', () => {
    const { rerender } = render(<Button size="default">預設尺寸</Button>);
    expect(screen.getByRole('button')).toHaveClass('h-9');
    
    rerender(<Button size="sm">小尺寸</Button>);
    expect(screen.getByRole('button')).toHaveClass('h-8');
    
    rerender(<Button size="lg">大尺寸</Button>);
    expect(screen.getByRole('button')).toHaveClass('h-10');
    
    rerender(<Button size="icon">圖標</Button>);
    expect(screen.getByRole('button')).toHaveClass('h-9', 'w-9');
  });
}); 