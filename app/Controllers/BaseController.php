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

        $selectedCompanyId = (int)($_SESSION['selected_company_id'] ?? 0);
        $isWorkspaceRoute = strpos((string)($_SERVER['REQUEST_URI'] ?? ''), '/company/workspace') !== false;
        if ($selectedCompanyId > 0 && !$isWorkspaceRoute && !(new CompanyModel())->isCompanyActive($selectedCompanyId)) {
            unset($_SESSION['selected_company_id']);
            $_SESSION['company_flash'] = 'The selected company is disabled. Choose an active company to continue.';
            header('Location: ' . BASE_URL . '/company/workspace');
            exit;
        }
    }

    protected function requireSuperAdmin(): void
    {
        $this->requireAccess();
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        if (!in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true)) {
            $_SESSION['company_flash'] = 'This module is available to Super Admin accounts only.';
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
        $moduleAliases = [$moduleKey];
        if ($moduleKey === 'payroll') {
            $moduleAliases[] = 'accounting';
        }
        if ($moduleKey === 'salary_structure') {
            $moduleAliases[] = 'payroll';
            $moduleAliases[] = 'accounting';
        }

        $hasCompanyAccess = false;
        foreach (array_unique($moduleAliases) as $alias) {
            if ($companyModel->hasModuleAccess($alias)) {
                $hasCompanyAccess = true;
                break;
            }
        }

        if (!$hasCompanyAccess) {
            $_SESSION['company_flash'] = 'This module is not enabled for the selected company.';
            $this->redirect('/');
        }

        $hasEmployeeAccess = false;
        foreach (array_unique($moduleAliases) as $alias) {
            if ($companyModel->hasCurrentUserModuleAccess($alias)) {
                $hasEmployeeAccess = true;
                break;
            }
        }
        $roleName = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        $isSuperAdmin = in_array($roleName, ['super admin', 'superadministrator', 'super administrator'], true);
        if (!$isSuperAdmin && !$hasEmployeeAccess) {
            $_SESSION['company_flash'] = 'This module is not enabled for your staff account.';
            $this->redirect('/');
        }
    }
}
