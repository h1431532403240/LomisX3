/**
 * TwoFactorSetup - 雙因子驗證設定組件
 * 
 * 提供完整的2FA設定流程
 * - QR碼生成和顯示
 * - 驗證碼測試
 * - 備用恢復碼生成
 * - 設定狀態管理
 * 
 * @author LomisX3 開發團隊
 * @version 1.0.0
 */

import React, { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { 
  Smartphone, 
  Shield, 
  Key, 
  Copy, 
  Eye, 
  EyeOff, 
  CheckCircle, 
  AlertCircle, 
  Download,
  QrCode
} from 'lucide-react';
import { useAuthStore } from '@/stores/authStore';

/**
 * 2FA設定步驟
 */
type SetupStep = 'qr-code' | 'verify' | 'backup-codes' | 'complete';

/**
 * 2FA設定組件屬性
 */
interface TwoFactorSetupProps {
  /** 是否已啟用2FA */
  isEnabled?: boolean;
  /** 設定完成回調 */
  onSetupComplete?: () => void;
  /** 停用回調 */
  onDisable?: () => void;
  /** 自定義 CSS 類別 */
  className?: string;
}

/**
 * 模擬的2FA設定資料
 */
const mock2FAData = {
  qrCodeUrl: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==',
  secretKey: 'JBSWY3DPEHPK3PXP',
  backupCodes: [
    '8B2F-3D9A',
    'C7E4-1F8G',
    '5H6J-9K2L',
    'M3N4-P5Q6',
    'R7S8-T9U0',
    'V1W2-X3Y4',
    'Z5A6-B7C8',
    'D9E0-F1G2'
  ]
};

/**
 * 雙因子驗證設定組件
 */
export const TwoFactorSetup: React.FC<TwoFactorSetupProps> = ({
  isEnabled = false,
  onSetupComplete,
  onDisable,
  className = '',
}) => {
  const [currentStep, setCurrentStep] = useState<SetupStep>('qr-code');
  const [verificationCode, setVerificationCode] = useState('');
  const [isVerifying, setIsVerifying] = useState(false);
  const [showSecret, setShowSecret] = useState(false);
  const [showDisableDialog, setShowDisableDialog] = useState(false);
  const [disableVerificationCode, setDisableVerificationCode] = useState('');
  const { toast } = useToast();
  const { user } = useAuthStore();

  /**
   * 複製到剪貼簿
   */
  const copyToClipboard = async (text: string, label: string) => {
    try {
      await navigator.clipboard.writeText(text);
      toast({
        title: "已複製",
        description: `${label}已複製到剪貼簿`,
      });
    } catch (error) {
      toast({
        title: "複製失敗",
        description: "無法複製到剪貼簿",
        variant: "destructive",
      });
    }
  };

  /**
   * 驗證2FA代碼
   */
  const handleVerifyCode = async () => {
    if (verificationCode.length !== 6) {
      toast({
        title: "驗證碼格式錯誤",
        description: "請輸入6位數的驗證碼",
        variant: "destructive",
      });
      return;
    }

    setIsVerifying(true);
    
    try {
      // 模擬API調用
      await new Promise(resolve => setTimeout(resolve, 1500));
      
      // 模擬驗證成功
      if (verificationCode === '123456') {
        setCurrentStep('backup-codes');
        toast({
          title: "驗證成功",
          description: "雙因子驗證已啟用",
        });
      } else {
        toast({
          title: "驗證失敗",
          description: "驗證碼不正確，請重新輸入",
          variant: "destructive",
        });
      }
    } catch (error) {
      toast({
        title: "驗證錯誤",
        description: "系統錯誤，請稍後再試",
        variant: "destructive",
      });
    } finally {
      setIsVerifying(false);
    }
  };

  /**
   * 停用2FA
   */
  const handleDisable2FA = async () => {
    if (disableVerificationCode.length !== 6) {
      toast({
        title: "驗證碼格式錯誤",
        description: "請輸入6位數的驗證碼",
        variant: "destructive",
      });
      return;
    }

    try {
      // 模擬API調用
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      setShowDisableDialog(false);
      onDisable?.();
      
      toast({
        title: "2FA已停用",
        description: "雙因子驗證已成功停用",
      });
    } catch (error) {
      toast({
        title: "停用失敗",
        description: "無法停用雙因子驗證",
        variant: "destructive",
      });
    }
  };

  /**
   * 完成設定
   */
  const handleCompleteSetup = () => {
    setCurrentStep('complete');
    onSetupComplete?.();
    
    toast({
      title: "設定完成",
      description: "雙因子驗證設定已完成",
    });
  };

  /**
   * 下載備用碼
   */
  const downloadBackupCodes = () => {
    const content = `LomisX3 雙因子驗證備用碼
使用者：${user?.display_name || user?.username}
生成時間：${new Date().toLocaleString()}

備用碼（請妥善保管）：
${mock2FAData.backupCodes.join('\n')}

注意事項：
- 每個備用碼只能使用一次
- 請將備用碼保存在安全的地方
- 如果遺失備用碼，請聯繫系統管理員`;

    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `lomis-2fa-backup-codes-${Date.now()}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    toast({
      title: "下載完成",
      description: "備用碼已下載，請妥善保管",
    });
  };

  if (isEnabled) {
    return (
      <Card className={className}>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Shield className="h-5 w-5 text-green-600" />
            雙因子驗證
            <Badge variant="default" className="bg-green-100 text-green-800">
              已啟用
            </Badge>
          </CardTitle>
          <CardDescription>
            您的帳號已啟用雙因子驗證，提供額外的安全保護
          </CardDescription>
        </CardHeader>

        <CardContent className="space-y-4">
          <Alert>
            <CheckCircle className="h-4 w-4" />
            <AlertDescription>
              雙因子驗證已啟用。登入時需要輸入驗證應用程式中的6位數代碼。
            </AlertDescription>
          </Alert>

          <div className="flex items-center justify-between">
            <div>
              <p className="font-medium">管理備用碼</p>
              <p className="text-sm text-muted-foreground">
                重新生成或查看您的備用登入碼
              </p>
            </div>
            <Button variant="outline">
              管理備用碼
            </Button>
          </div>

          <Separator />

          <div className="flex items-center justify-between">
            <div>
              <p className="font-medium text-red-600">停用雙因子驗證</p>
              <p className="text-sm text-muted-foreground">
                停用後將降低帳號安全性
              </p>
            </div>
            
            <Dialog open={showDisableDialog} onOpenChange={setShowDisableDialog}>
              <DialogTrigger asChild>
                <Button variant="destructive">停用</Button>
              </DialogTrigger>
              <DialogContent>
                <DialogHeader>
                  <DialogTitle className="flex items-center gap-2">
                    <AlertCircle className="h-5 w-5 text-red-600" />
                    停用雙因子驗證
                  </DialogTitle>
                  <DialogDescription>
                    請輸入當前的6位數驗證碼以確認停用雙因子驗證
                  </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                  <div>
                    <Label htmlFor="disable-code">驗證碼</Label>
                    <Input
                      id="disable-code"
                      type="text"
                      placeholder="請輸入6位數驗證碼"
                      value={disableVerificationCode}
                      onChange={(e) => setDisableVerificationCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                      maxLength={6}
                      className="text-center text-lg tracking-widest"
                    />
                  </div>

                  <Alert>
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>
                      停用雙因子驗證將降低您的帳號安全性，建議保持啟用狀態。
                    </AlertDescription>
                  </Alert>

                  <div className="flex justify-end space-x-2">
                    <Button variant="outline" onClick={() => setShowDisableDialog(false)}>
                      取消
                    </Button>
                    <Button variant="destructive" onClick={handleDisable2FA}>
                      確認停用
                    </Button>
                  </div>
                </div>
              </DialogContent>
            </Dialog>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Smartphone className="h-5 w-5 text-blue-600" />
          設定雙因子驗證
        </CardTitle>
        <CardDescription>
          為您的帳號添加額外的安全保護層
        </CardDescription>
      </CardHeader>

      <CardContent>
        <Tabs value={currentStep} className="w-full">
          <TabsList className="grid w-full grid-cols-4">
            <TabsTrigger value="qr-code" disabled={currentStep !== 'qr-code'}>
              掃描QR碼
            </TabsTrigger>
            <TabsTrigger value="verify" disabled={currentStep !== 'verify'}>
              驗證
            </TabsTrigger>
            <TabsTrigger value="backup-codes" disabled={currentStep !== 'backup-codes'}>
              備用碼
            </TabsTrigger>
            <TabsTrigger value="complete" disabled={currentStep !== 'complete'}>
              完成
            </TabsTrigger>
          </TabsList>

          {/* 步驟1：掃描QR碼 */}
          <TabsContent value="qr-code" className="space-y-4">
            <Alert>
              <QrCode className="h-4 w-4" />
              <AlertDescription>
                使用您的驗證應用程式（如 Google Authenticator、Authy）掃描下方的QR碼
              </AlertDescription>
            </Alert>

            <div className="flex flex-col items-center space-y-4">
              <div className="p-4 bg-white rounded-lg border">
                <img 
                  src={mock2FAData.qrCodeUrl}
                  alt="2FA QR Code"
                  className="w-48 h-48"
                />
              </div>

              <div className="text-center space-y-2">
                <p className="text-sm text-muted-foreground">
                  無法掃描QR碼？手動輸入密鑰：
                </p>
                <div className="flex items-center space-x-2">
                  <code className="px-2 py-1 bg-muted rounded text-sm font-mono">
                    {showSecret ? mock2FAData.secretKey : '••••••••••••••••'}
                  </code>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => setShowSecret(!showSecret)}
                  >
                    {showSecret ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => copyToClipboard(mock2FAData.secretKey, '密鑰')}
                  >
                    <Copy className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </div>

            <div className="flex justify-end">
              <Button onClick={() => setCurrentStep('verify')}>
                下一步：驗證
              </Button>
            </div>
          </TabsContent>

          {/* 步驟2：驗證代碼 */}
          <TabsContent value="verify" className="space-y-4">
            <Alert>
              <Key className="h-4 w-4" />
              <AlertDescription>
                請輸入驗證應用程式中顯示的6位數驗證碼
              </AlertDescription>
            </Alert>

            <div className="space-y-4">
              <div>
                <Label htmlFor="verification-code">驗證碼</Label>
                <Input
                  id="verification-code"
                  type="text"
                  placeholder="請輸入6位數驗證碼"
                  value={verificationCode}
                  onChange={(e) => setVerificationCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                  maxLength={6}
                  className="text-center text-lg tracking-widest"
                />
                <p className="text-xs text-muted-foreground mt-1">
                  測試提示：輸入 123456 進行驗證
                </p>
              </div>

              <div className="flex justify-between">
                <Button variant="outline" onClick={() => setCurrentStep('qr-code')}>
                  上一步
                </Button>
                <Button 
                  onClick={handleVerifyCode}
                  disabled={verificationCode.length !== 6 || isVerifying}
                >
                  {isVerifying ? '驗證中...' : '驗證並繼續'}
                </Button>
              </div>
            </div>
          </TabsContent>

          {/* 步驟3：備用碼 */}
          <TabsContent value="backup-codes" className="space-y-4">
            <Alert>
              <Download className="h-4 w-4" />
              <AlertDescription>
                請將這些備用碼保存在安全的地方。如果無法使用驗證應用程式，可以使用這些備用碼登入。
              </AlertDescription>
            </Alert>

            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-2 p-4 bg-muted rounded-lg">
                {mock2FAData.backupCodes.map((code, index) => (
                  <div key={index} className="flex items-center justify-between p-2 bg-background rounded border">
                    <code className="font-mono text-sm">{code}</code>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => copyToClipboard(code, `備用碼 ${index + 1}`)}
                    >
                      <Copy className="h-3 w-3" />
                    </Button>
                  </div>
                ))}
              </div>

              <div className="flex justify-center">
                <Button variant="outline" onClick={downloadBackupCodes}>
                  <Download className="h-4 w-4 mr-2" />
                  下載備用碼
                </Button>
              </div>

              <Alert>
                <AlertCircle className="h-4 w-4" />
                <AlertDescription>
                  <strong>重要提醒：</strong>
                  <ul className="list-disc list-inside mt-2 space-y-1">
                    <li>每個備用碼只能使用一次</li>
                    <li>請將備用碼保存在安全的地方</li>
                    <li>不要與他人分享這些備用碼</li>
                    <li>如果遺失備用碼，請聯繫系統管理員</li>
                  </ul>
                </AlertDescription>
              </Alert>

              <div className="flex justify-end">
                <Button onClick={handleCompleteSetup}>
                  完成設定
                </Button>
              </div>
            </div>
          </TabsContent>

          {/* 步驟4：設定完成 */}
          <TabsContent value="complete" className="space-y-4">
            <div className="text-center space-y-4">
              <div className="flex justify-center">
                <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                  <CheckCircle className="h-8 w-8 text-green-600" />
                </div>
              </div>

              <div>
                <h3 className="text-lg font-semibold">雙因子驗證設定完成！</h3>
                <p className="text-muted-foreground">
                  您的帳號現在受到雙因子驗證保護，安全性大幅提升。
                </p>
              </div>

              <Alert>
                <Shield className="h-4 w-4" />
                <AlertDescription>
                  下次登入時，您需要輸入密碼以及驗證應用程式中的6位數驗證碼。
                </AlertDescription>
              </Alert>
            </div>
          </TabsContent>
        </Tabs>
      </CardContent>
    </Card>
  );
};

export default TwoFactorSetup; 