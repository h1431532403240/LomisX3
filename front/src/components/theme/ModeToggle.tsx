"use client"

import { Moon, Sun } from "lucide-react"
import { useTheme } from "next-themes"

import { Button } from "@/components/ui/button"

/**
 * 主題切換組件
 * 提供淺色和深色主題的直接切換功能
 * 點擊按鈕即可在兩種主題間切換
 */
export function ModeToggle() {
  const { theme, setTheme } = useTheme()

  /**
   * 切換主題函數
   * 在淺色和深色之間切換
   */
  const toggleTheme = () => {
    setTheme(theme === "dark" ? "light" : "dark")
  }

  return (
    <Button 
      variant="outline" 
      size="icon"
      className="h-8 w-8"
      onClick={toggleTheme}
      title={theme === "dark" ? "切換至淺色主題" : "切換至深色主題"}
    >
      <Sun className="h-[1.2rem] w-[1.2rem] rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
      <Moon className="absolute h-[1.2rem] w-[1.2rem] rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
      <span className="sr-only">切換主題</span>
    </Button>
  )
} 