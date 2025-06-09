/**
 * Pages 元件統一匯出檔案
 * 提供頁面元件的中央匯出點
 */

// Dashboard
export { Dashboard } from './Dashboard';
export { default as DashboardPage } from './dashboard/DashboardPage';

// Users
export { UsersPage } from './users';
export { CreateUserPage } from './users/CreateUserPage';
export { EditUserPage } from './users/EditUserPage';
export { UserDetailPage } from './users/UserDetailPage';
export { default as PermissionsPage } from './users/PermissionsPage';

// Roles
export { default as RolesPage } from './roles/RolesPage';

// Profile
export { default as ProfilePage } from './profile/ProfilePage';

// Auth
export { default as LoginPage } from './auth/LoginPage'; 