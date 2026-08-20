<?php
/**
 * Authentication controller.
 */
declare(strict_types=1);

require_once APP_ROOT . '/core/Controller.php';
require_once APP_ROOT . '/core/Auth.php';
require_once APP_ROOT . '/app/Models/UserModel.php';
require_once APP_ROOT . '/app/Models/RoleModel.php';
require_once APP_ROOT . '/app/Models/CompanyModel.php';

class AuthController extends Controller
{
    public function login(): void
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $error = '';
        $success = '';
        $companyModel = new CompanyModel();
        $companies = $companyModel->getCompanies();
        $emailValue = $_COOKIE['remember_email'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $emailValue = trim((string)($_GET['email'] ?? $emailValue));
        }

        if (!empty($_SESSION['user'])) {
            $roleName = strtolower((string)($_SESSION['user']['role_name'] ?? ''));
            if (in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true)) {
                $this->redirect('/company/workspace');
            }
            if ($roleName === 'staff') {
                $this->redirect('/portal/staff');
            }
            $this->redirect('/');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
                $error = 'Invalid session token. Please try again.';
            } else {
                $email = trim($_POST['email'] ?? '');
                $password = trim($_POST['password'] ?? '');
                $companyId = (int)($_POST['company_id'] ?? 0);
                $remember = !empty($_POST['remember_me']);

                if ($email === '' || $password === '' || $companyId <= 0) {
                    $error = 'Please enter your email, password, and select a company.';
                    $emailValue = $email;
                } elseif (!$companyModel->isCompanyActive($companyId)) {
                    $error = 'The selected company is not available.';
                } else {
                    $model = new UserModel();
                    $roleModel = new RoleModel();
                    $roleModel->ensureStandardRoleSet();
                    $model->ensureSuperAdminUser();
                    $model->ensureTestLogisticsAccounts();
                    $model->populateUsersFromEmployees();
                    $user = $model->authenticate($email, $password, $companyId);
                    if ($user) {
                        $_SESSION['selected_company_id'] = $companyId;
                        $user = $model->syncEmployeeProfile($user);
                    }
                    if ($user) {
                        $roleModel = new RoleModel();
                        $user['role_name'] = $roleModel->getRoleNameById((int)($user['role_id'] ?? 0));
                        $roleName = strtolower((string)($user['role_name'] ?? ''));
                        if (in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true)) {
                            $error = 'Super Admins must use the Super Admin portal.';
                            $emailValue = $email;
                            $user = null;
                        }
                    }
                    if ($user) {
                        $_SESSION['user'] = $user;
                        $roleName = strtolower((string)($user['role_name'] ?? ''));
                        if ($remember) {
                            setcookie('remember_email', $email, time() + 60 * 60 * 24 * 30, '/');
                        } else {
                            setcookie('remember_email', '', time() - 3600, '/');
                        }
                        if ($roleName === 'super admin' || $roleName === 'superadministrator' || $roleName === 'super administrator') {
                            $this->redirect('/company/workspace');
                        }
                        if ($roleName === 'staff') {
                            $this->redirect('/portal/staff');
                        }
                        $portalRoute = $this->portalRouteForRole($user['role_name'] ?? '');
                        $this->redirect($portalRoute !== '/' ? $portalRoute : '/');
                    }

                    $error = 'Invalid email or password.';
                    $emailValue = $email;
                }
            }
        }

        $this->view('auth/login', [
            'title' => 'Login',
            'error' => $error,
            'success' => $success,
            'emailValue' => $emailValue,
            'companies' => $companies,
            'csrfToken' => $_SESSION['csrf_token'],
        ]);
    }

    public function superAdminLogin(): void
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $error = '';
        $companyModel = new CompanyModel();
        $companies = $companyModel->getCompanies();

        if (!empty($_SESSION['user'])) {
            $roleName = strtolower((string)($_SESSION['user']['role_name'] ?? ''));
            if (in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true)) {
                $this->redirect('/company/workspace');
            }
            $error = 'This portal is for Super Admin accounts only.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = (string)($_POST['csrf_token'] ?? '');
            $email = trim((string)($_POST['email'] ?? ''));
            $password = trim((string)($_POST['password'] ?? ''));
            $companyId = (int)($_POST['company_id'] ?? 0);

            if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
                $error = 'Invalid session token. Please try again.';
            } elseif ($email === '' || $password === '' || $companyId <= 0) {
                $error = 'Enter your credentials and select an active company.';
            } elseif (!$companyModel->isCompanyActive($companyId)) {
                $error = 'The selected company is not available.';
            } else {
                $model = new UserModel();
                $roleModel = new RoleModel();
                $roleModel->ensureStandardRoleSet();
                $model->ensureSuperAdminUser();
                $model->populateUsersFromEmployees();
                $user = $model->authenticate($email, $password, $companyId);
                if ($user) {
                    $user['role_name'] = $roleModel->getRoleNameById((int)($user['role_id'] ?? 0));
                }
                $roleName = strtolower((string)($user['role_name'] ?? ''));
                if ($user && in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true)) {
                    $_SESSION['selected_company_id'] = $companyId;
                    $_SESSION['user'] = $user;
                    $this->redirect('/company/workspace');
                }
                $error = 'Invalid Super Admin credentials.';
            }
        }

        $this->view('auth/super_admin_login', [
            'title' => 'Super Admin Login',
            'error' => $error,
            'companies' => $companies,
            'csrfToken' => $_SESSION['csrf_token'],
        ]);
    }

    public function logout(): void
    {
        session_destroy();
        $this->redirect('/login');
    }

    public function stopImpersonation(): void
    {
        if (!empty($_SESSION['impersonation_admin_user'])) {
            $_SESSION['user'] = $_SESSION['impersonation_admin_user'];
            $_SESSION['selected_company_id'] = (int)($_SESSION['impersonation_company_id'] ?? ($_SESSION['selected_company_id'] ?? 1));
            unset($_SESSION['impersonation_admin_user'], $_SESSION['impersonation_company_id']);
        }
        $this->redirect('/management/employees');
    }

    private function ensureDefaultRoles(RoleModel $roleModel): void
    {
        $defaultRoles = RoleModel::defaultRoleNames();
        foreach ($defaultRoles as $role) {
            $roleModel->createRoleIfMissing($role, $role . ' privilege');
        }
    }

    private function portalRouteForRole(string $roleName): string
    {
        $name = strtolower(trim($roleName));

        if ($name === 'staff') {
            return '/portal/staff';
        }
        if ($name === 'managing director' || $name === 'managing_director') {
            return '/portal/managing-director';
        }
        if ($name === 'super admin' || $name === 'superadministrator' || $name === 'super administrator') {
            return '/portal/super-admin';
        }
        if ($name === 'executive director finance and admin' || $name === 'executive director finance and admin ') {
            return '/portal/admin';
        }
        if ($name === 'executive director operator') {
            return '/portal/admin';
        }
        if ($name === 'general manager contract admin' || $name === 'general manager project management' || $name === 'general manager business development') {
            return '/portal/admin';
        }
        if ($name === 'deputy general manager special services' || $name === 'deputy general manager finance' || $name === 'internal auditor') {
            return '/portal/admin';
        }
        if ($name === 'head store' || $name === 'head mechanic') {
            return '/portal/admin';
        }
        if ($name === 'human resource manager' || $name === 'human resource officer') {
            return '/portal/hr-manager';
        }
        if ($name === 'ict administrator') {
            return '/portal/admin';
        }
        if ($name === 'procurement officer' || $name === 'procurement_officer') {
            return '/portal/procurement-officer';
        }
        if ($name === 'logistics officer' || $name === 'logistics_officer') {
            return '/portal/admin';
        }
        if ($name === 'architect' || $name === 'builder' || $name === 'quantity surveyor' || $name === 'carpenter' || $name === 'welder' || $name === 'secretary' || $name === 'receptionist' || $name === 'store keeper' || $name === 'accountant' || $name === 'payroll manager' || $name === 'asset manager' || $name === 'cashier' || $name === 'chief security officer') {
            return '/portal/admin';
        }

        return '/';
    }
}
