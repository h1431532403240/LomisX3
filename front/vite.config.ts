import path from "path"
import react from "@vitejs/plugin-react"
import { defineConfig } from "vite"

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  
  // ✅ 代理配置：解決前後端分離開發時的 API 請求問題
  server: {
    proxy: {
      // ✅ 將所有以 /api 開頭的請求，代理到後端的 8000 端口
      '/api': {
        target: 'http://localhost:8000', // 後端 Laravel 服務地址
        changeOrigin: true, // 必須設置為 true，後端才能正確識別來源
        secure: false, // 如果後端是 http，設置為 false
        // 可選：重寫路徑，如果後端不需要 /api 前綴
        // rewrite: (path) => path.replace(/^\/api/, ''), 
      },
      // ✅ Laravel Sanctum CSRF Cookie 端點代理
      '/sanctum': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      }
    }
  },
  
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
})
