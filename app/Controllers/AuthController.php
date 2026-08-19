<?php
/**
 * Authentication controller.
 */
declare(strict_types=1);

require_once APP_ROOT . '/core/Controller.php';
require_once APP_ROOT . '/core/Auth.php';
require_once APP_ROOT . '/app/Models/UserModel.php';
require_once APP_ROOT . '/app/Models/RoleModel.php';

class AuthController extends Controller
{
    public function login(): void
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $error = '';
        $success = '';
        $emailValue = $_COOKIE['remember_email'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $emailValue = trim((string)($_GET['email'] ?? $emailValue));
        }

        if (!empty($_SESSION['user'])) {
            $roleName = strtolower((string)($_SESSION['user']['role_name'] ?? ''));
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
                $remember = !empty($_POST['remember_me']);

                if ($email === '' || $password === '') {
                    $error = 'Please enter both your email and password.';
                    $emailValue = $email;
                } else {
                    $model = new UserModel();
                    $roleModel = new RoleModel();
                    $roleModel->ensureStandardRoleSet();
                    $model->ensureSuperAdminUser();
                    $model->ensureTestLogisticsAccounts();
                    $user = $model->authenticate($email, $password);
                    if ($user) {
                        $user = $model->syncEmployeeProfile($user);
                    }
                    if ($user) {
                        $roleModel = new RoleModel();
                        $user['role_name'] = $roleModel->getRoleNameById((int)($user['role_id'] ?? 0));
                        $_SESSION['user'] = $user;
                        $roleName = strtolower((string)($user['role_name'] ?? ''));
                        if ($remember) {
                            setcookie('remember_email', $email, time() + 60 * 60 * 24 * 30, '/');
                        } else {
                            setcookie('remember_email', '', time() - 3600, '/');
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
            'csrfToken' => $_SESSION['csrf_token'],
        ]);
    }

    public function logout(): void
    {
        session_destroy();
        $this->redirect('/login');
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
