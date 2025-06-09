/**
 * ProfilePage - 個人設定頁面
 * 
 * 使用者個人設定和帳戶管理
 * - 個人資訊編輯
 * - 密碼變更
 * - 安全設定
 * - 偏好設定
 * 
 * @author LomisX3 開發團隊
 * @version 1.0.0
 */

import React, { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Separator } from '@/components/ui/separator';
import { 
  User, 
  Lock, 
  Shield, 
  Bell, 
  Settings, 
  Camera,
  Eye,
  EyeOff,
  Key,
  Smartphone,
  Mail,
  Phone,
  MapPin,
  Calendar,
  Clock,
  Save
} from 'lucide-react';
import { PageHeader } from '@/components/common/breadcrumb';
import { ResponsiveContainer } from '@/components/common/responsive-container';

/**
 * 模擬使用者資料
 */
const mockUser = {
  id: 1,
  name: '張小明',
  username: 'zhang.xiaoming',
  email: 'zhang.xiaoming@example.com',
  phone: '0912-345-678',
  avatar: '',
  role: '門市經理',
  store: '總店',
  joinDate: '2023-01-15',
  lastLogin: '2024-01-15 14:30',
  twoFactorEnabled: false,
  emailVerified: true,
  notifications: {
    email: true,
    sms: false,
    push: true,
  }
};

/**
 * 個人設定頁面組件
 */
export default function ProfilePage() {
  const [showCurrentPassword, setShowCurrentPassword] = useState(false);
  const [showNewPassword, setShowNewPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  return (
    <ResponsiveContainer maxWidth="7xl" padding="default">
      <div className="space-y-6">
        {/* 頁面標題和麵包屑 */}
        <PageHeader
          title="個人設定"
          description="管理您的個人資訊和帳戶設定"
        />

      {/* 使用者資訊概覽 */}
      <Card>
        <CardContent className="p-6">
          <div className="flex items-center space-x-4">
            <div className="relative">
              <Avatar className="h-20 w-20">
                <AvatarImage src={mockUser.avatar} />
                <AvatarFallback className="bg-blue-600 text-white text-xl">
                  {mockUser.name.charAt(0)}
                </AvatarFallback>
              </Avatar>
              <Button size="sm" className="absolute -bottom-2 -right-2 h-8 w-8 rounded-full p-0">
                <Camera className="h-4 w-4" />
              </Button>
            </div>
            
            <div className="space-y-1">
              <h2 className="text-2xl font-semibold">{mockUser.name}</h2>
              <p className="text-muted-foreground">@{mockUser.username}</p>
              <div className="flex items-center space-x-2">
                <Badge variant="default">{mockUser.role}</Badge>
                <Badge variant="outline">{mockUser.store}</Badge>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* 設定標籤頁 */}
      <Tabs defaultValue="profile" className="space-y-4">
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="profile">個人資訊</TabsTrigger>
          <TabsTrigger value="security">安全設定</TabsTrigger>
          <TabsTrigger value="notifications">通知設定</TabsTrigger>
          <TabsTrigger value="preferences">偏好設定</TabsTrigger>
        </TabsList>

        {/* 個人資訊 */}
        <TabsContent value="profile" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <User className="h-5 w-5" />
                基本資訊
              </CardTitle>
              <CardDescription>
                更新您的個人基本資訊
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="name">姓名</Label>
                  <Input id="name" defaultValue={mockUser.name} />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="username">用戶名</Label>
                  <Input id="username" defaultValue={mockUser.username} />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="email">電子郵件</Label>
                  <div className="relative">
                    <Mail className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                    <Input id="email" type="email" className="pl-10" defaultValue={mockUser.email} />
                  </div>
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="phone">電話</Label>
                  <div className="relative">
                    <Phone className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                    <Input id="phone" type="tel" className="pl-10" defaultValue={mockUser.phone} />
                  </div>
                </div>
              </div>
              
              <div className="flex justify-end">
                <Button>
                  <Save className="h-4 w-4 mr-2" />
                  儲存變更
                </Button>
              </div>
            </CardContent>
          </Card>

          {/* 帳戶資訊 */}
          <Card>
            <CardHeader>
              <CardTitle>帳戶資訊</CardTitle>
              <CardDescription>
                您的帳戶基本資訊
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="flex items-center space-x-2">
                  <Calendar className="h-4 w-4 text-muted-foreground" />
                  <span className="text-sm text-muted-foreground">加入日期：</span>
                  <span className="text-sm font-medium">{mockUser.joinDate}</span>
                </div>
                
                <div className="flex items-center space-x-2">
                  <Clock className="h-4 w-4 text-muted-foreground" />
                  <span className="text-sm text-muted-foreground">最後登入：</span>
                  <span className="text-sm font-medium">{mockUser.lastLogin}</span>
                </div>
                
                <div className="flex items-center space-x-2">
                  <MapPin className="h-4 w-4 text-muted-foreground" />
                  <span className="text-sm text-muted-foreground">所屬門市：</span>
                  <Badge variant="outline">{mockUser.store}</Badge>
                </div>
                
                <div className="flex items-center space-x-2">
                  <Shield className="h-4 w-4 text-muted-foreground" />
                  <span className="text-sm text-muted-foreground">角色：</span>
                  <Badge variant="default">{mockUser.role}</Badge>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* 安全設定 */}
        <TabsContent value="security" className="space-y-4">
          {/* 密碼變更 */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Lock className="h-5 w-5" />
                變更密碼
              </CardTitle>
              <CardDescription>
                定期更新密碼以保護您的帳戶安全
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="current-password">目前密碼</Label>
                <div className="relative">
                  <Lock className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                  <Input 
                    id="current-password" 
                    type={showCurrentPassword ? "text" : "password"}
                    className="pl-10 pr-10"
                    placeholder="請輸入目前密碼"
                  />
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                    onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                  >
                    {showCurrentPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </Button>
                </div>
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="new-password">新密碼</Label>
                <div className="relative">
                  <Lock className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                  <Input 
                    id="new-password" 
                    type={showNewPassword ? "text" : "password"}
                    className="pl-10 pr-10"
                    placeholder="請輸入新密碼"
                  />
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                    onClick={() => setShowNewPassword(!showNewPassword)}
                  >
                    {showNewPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </Button>
                </div>
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="confirm-password">確認新密碼</Label>
                <div className="relative">
                  <Lock className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                  <Input 
                    id="confirm-password" 
                    type={showConfirmPassword ? "text" : "password"}
                    className="pl-10 pr-10"
                    placeholder="請再次輸入新密碼"
                  />
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                    onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                  >
                    {showConfirmPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </Button>
                </div>
              </div>
              
              <Button>
                <Key className="h-4 w-4 mr-2" />
                更新密碼
              </Button>
            </CardContent>
          </Card>

          {/* 雙因子驗證 */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Smartphone className="h-5 w-5" />
                雙因子驗證 (2FA)
              </CardTitle>
              <CardDescription>
                增強您的帳戶安全性
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between">
                <div>
                  <h4 className="font-medium">啟用雙因子驗證</h4>
                  <p className="text-sm text-muted-foreground">
                    使用手機應用程式進行額外的安全驗證
                  </p>
                </div>
                <Switch checked={mockUser.twoFactorEnabled} />
              </div>
              
              {!mockUser.twoFactorEnabled && (
                <Button variant="outline">
                  <Shield className="h-4 w-4 mr-2" />
                  設定 2FA
                </Button>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* 通知設定 */}
        <TabsContent value="notifications" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Bell className="h-5 w-5" />
                通知偏好
              </CardTitle>
              <CardDescription>
                選擇您希望接收的通知類型
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <div>
                    <h4 className="font-medium">電子郵件通知</h4>
                    <p className="text-sm text-muted-foreground">
                      接收重要更新和系統通知
                    </p>
                  </div>
                  <Switch checked={mockUser.notifications.email} />
                </div>
                
                <Separator />
                
                <div className="flex items-center justify-between">
                  <div>
                    <h4 className="font-medium">簡訊通知</h4>
                    <p className="text-sm text-muted-foreground">
                      接收緊急通知和安全警報
                    </p>
                  </div>
                  <Switch checked={mockUser.notifications.sms} />
                </div>
                
                <Separator />
                
                <div className="flex items-center justify-between">
                  <div>
                    <h4 className="font-medium">推播通知</h4>
                    <p className="text-sm text-muted-foreground">
                      在瀏覽器中接收即時通知
                    </p>
                  </div>
                  <Switch checked={mockUser.notifications.push} />
                </div>
              </div>
              
              <Button>
                <Save className="h-4 w-4 mr-2" />
                儲存設定
              </Button>
            </CardContent>
          </Card>
        </TabsContent>

        {/* 偏好設定 */}
        <TabsContent value="preferences" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Settings className="h-5 w-5" />
                介面偏好
              </CardTitle>
              <CardDescription>
                自訂您的使用體驗
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <div>
                    <h4 className="font-medium">深色模式</h4>
                    <p className="text-sm text-muted-foreground">
                      使用深色主題減少眼部疲勞
                    </p>
                  </div>
                  <Switch />
                </div>
                
                <Separator />
                
                <div className="flex items-center justify-between">
                  <div>
                    <h4 className="font-medium">自動儲存</h4>
                    <p className="text-sm text-muted-foreground">
                      自動儲存表單內容
                    </p>
                  </div>
                  <Switch defaultChecked />
                </div>
                
                <Separator />
                
                <div className="flex items-center justify-between">
                  <div>
                    <h4 className="font-medium">鍵盤快捷鍵</h4>
                    <p className="text-sm text-muted-foreground">
                      啟用鍵盤快捷鍵功能
                    </p>
                  </div>
                  <Switch defaultChecked />
                </div>
              </div>
              
              <Button>
                <Save className="h-4 w-4 mr-2" />
                儲存偏好
              </Button>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
      </div>
    </ResponsiveContainer>
  );
} 