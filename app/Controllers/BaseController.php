<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Controller.php';
require_once APP_ROOT . '/app/Models/CompanyModel.php';

class BaseController extends Controller
{
    protected function requireAccess(): void
    {
        if (empty($_SESSION['user'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    protected function requireSuperAdmin(): void
    {
        $this->requireAccess();
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        if (!in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true)) {
            $_SESSION['company_flash'] = 'Super Admin access is required for this page.';
            $this->redirect('/');
        }
    }

    protected function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    protected function requireCompanyModule(string $moduleKey): void
    {
        $this->requireAccess();
        $companyModel = new CompanyModel();
        if (!$companyModel->hasCurrentUserModuleAccess($moduleKey)) {
            $_SESSION['company_flash'] = 'This module is not enabled for your staff account.';
            $this->redirect('/');
        }
    }
}
