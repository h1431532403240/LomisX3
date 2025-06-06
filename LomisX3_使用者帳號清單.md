# 🔑 LomisX3 企業級管理系統 - 使用者帳號清單

## 🚨 重要安全提醒
**預設密碼：`password123`**  
⚠️ **生產環境請立即修改所有預設密碼！**

---

## 👥 完整使用者帳號清單

### 🔥 系統管理員 (Level 100)
| 使用者名稱 | 帳號 | 密碼 | 角色 | 門市 | Email | 權限說明 |
|-----------|------|------|------|------|-------|----------|
| 系統管理員 | `admin` | `password123` | admin | LomisX3 總公司 | admin@lomisx3.com | 系統所有權限，可跨門市操作 |

### 🏢 總公司管理人員 (Level 80)
| 使用者名稱 | 帳號 | 密碼 | 角色 | 門市 | Email | 權限說明 |
|-----------|------|------|------|------|-------|----------|
| 總部經理 | `hq.manager` | `password123` | store_admin | LomisX3 總公司 | hq.manager@lomisx3.com | 總公司營運管理 |

### 🌍 區域經理 (Level 80)
| 使用者名稱 | 帳號 | 密碼 | 角色 | 門市 | Email | 權限說明 |
|-----------|------|------|------|------|-------|----------|
| 北區經理 | `north.manager` | `password123` | store_admin | 北區營運中心 | north.manager@lomisx3.com | 北區所有門市管理權限 |
| 中區經理 | `central.manager` | `password123` | store_admin | 中區營運中心 | central.manager@lomisx3.com | 中區所有門市管理權限 |
| 南區經理 | `south.manager` | `password123` | store_admin | 南區營運中心 | south.manager@lomisx3.com | 南區所有門市管理權限 |

### 🏪 門市店長 (Level 60)
| 使用者名稱 | 帳號 | 密碼 | 角色 | 門市 | Email | 權限說明 |
|-----------|------|------|------|------|-------|----------|
| 台北店長 | `taipei.manager` | `password123` | manager | 台北旗艦店 | taipei.manager@lomisx3.com | 台北旗艦店管理權限 |
| 信義店長 | `xinyi.manager` | `password123` | manager | 信義分店 | xinyi.manager@lomisx3.com | 信義分店管理權限 |
| 台中店長 | `taichung.manager` | `password123` | manager | 台中逢甲店 | taichung.manager@lomisx3.com | 台中逢甲店管理權限 |
| 高雄店長 | `kaohsiung.manager` | `password123` | manager | 高雄夢時代店 | kaohsiung.manager@lomisx3.com | 高雄夢時代店管理權限 |

### 👨‍💼 一般員工 (Level 40)
| 使用者名稱 | 帳號 | 密碼 | 角色 | 門市 | Email | 權限說明 |
|-----------|------|------|------|------|-------|----------|
| 台北員工A | `taipei.staff1` | `password123` | staff | 台北旗艦店 | taipei.staff1@lomisx3.com | 台北旗艦店銷售權限 |
| 台北員工B | `taipei.staff2` | `password123` | staff | 台北旗艦店 | taipei.staff2@lomisx3.com | 台北旗艦店庫存權限 |
| 信義員工 | `xinyi.staff` | `password123` | staff | 信義分店 | xinyi.staff@lomisx3.com | 信義分店銷售權限 |
| 台中員工 | `taichung.staff` | `password123` | staff | 台中逢甲店 | taichung.staff@lomisx3.com | 台中逢甲店銷售權限 |

### 👤 測試/訪客帳號 (Level 20)
| 使用者名稱 | 帳號 | 密碼 | 角色 | 門市 | Email | 權限說明 |
|-----------|------|------|------|------|-------|----------|
| 測試使用者 | `testuser` | `password123` | guest | 台北旗艦店 | test@example.com | 系統測試專用，最低權限 |
| 示範訪客 | `demo.guest` | `password123` | guest | 台北旗艦店 | demo@lomisx3.com | 系統展示用，最低權限 |

---

## 🔐 角色權限階層說明

### Level 100 - 系統管理員 (admin)
- ✅ 系統所有功能完整存取權限
- ✅ 可跨門市操作和管理
- ✅ 使用者管理、角色管理、系統設定
- ✅ 完整的商品分類、門市管理權限

### Level 80 - 門市管理員 (store_admin)  
- ✅ 所屬門市/區域完整管理權限
- ✅ 使用者管理（限門市範圍）
- ✅ 商品分類管理、門市營運管理
- ❌ 無系統級設定權限

### Level 60 - 部門主管 (manager)
- ✅ 門市日常營運管理
- ✅ 商品分類讀取和基本編輯
- ❌ 無使用者管理權限
- ❌ 無門市設定權限

### Level 40 - 一般員工 (staff)
- ✅ 基本業務功能操作
- ✅ 商品分類查看
- ❌ 無管理功能權限
- ❌ 無設定修改權限

### Level 20 - 訪客 (guest)
- ✅ 基本系統查看功能
- ❌ 無編輯修改權限
- ❌ 僅用於展示和測試

---

## 🚀 快速登入測試

### 管理員登入測試
```bash
帳號: admin
密碼: password123
```

### 店長登入測試  
```bash
帳號: taipei.manager
密碼: password123
```

### 員工登入測試
```bash
帳號: taipei.staff1  
密碼: password123
```

---

## ⚠️ 生產環境安全檢查清單

- [ ] **立即修改所有預設密碼**
- [ ] **啟用雙因子驗證 (2FA)**  
- [ ] **設定密碼複雜性要求**
- [ ] **啟用登入失敗鎖定機制**
- [ ] **定期檢查使用者權限**
- [ ] **監控異常登入活動**
- [ ] **設定密碼定期更新策略**
- [ ] **移除不必要的測試帳號**

---

## 📊 統計資訊

- **使用者總數**: 15位
- **系統管理員**: 1位  
- **門市管理員**: 4位
- **部門主管**: 4位
- **一般員工**: 4位
- **訪客**: 2位
- **涵蓋門市**: 11個門市完整覆蓋
- **門市隔離**: ✅ 已實現多租戶隔離機制

**系統已完全就緒，可以開始具體的業務功能開發！** 🎉 