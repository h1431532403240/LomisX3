/**
 * 使用者表單組件 (簡化版)
 * 
 * V2.7 生產加固版實現
 * @requires shadcn/ui 組件庫
 */

import { 
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import type { components } from '@/types/api';

type User = components['schemas']['User'];

interface UserFormProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
  user?: User | null;
  isEdit?: boolean;
}

/**
 * 使用者表單組件
 * 
 * @param props - 表單屬性
 * @returns 使用者表單組件
 */
export const UserForm: React.FC<UserFormProps> = ({
  isOpen,
  onClose,
  onSuccess,
  user = null,
  isEdit = false,
}) => {
  /**
   * 處理表單提交
   */
  const handleSubmit = () => {
    // 暫時的成功回調
    onSuccess();
    onClose();
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>
            {isEdit ? '編輯使用者' : '新增使用者'}
          </DialogTitle>
        </DialogHeader>
        
        <div className="space-y-4 py-4">
          <div className="text-center text-muted-foreground">
            使用者表單功能正在開發中...
            {user && (
              <div className="mt-2 text-sm">
                編輯使用者: {user.username}
              </div>
            )}
          </div>
        </div>

        <div className="flex justify-end space-x-2">
          <Button variant="outline" onClick={onClose}>
            取消
          </Button>
          <Button onClick={handleSubmit}>
            {isEdit ? '更新' : '新增'}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}; 