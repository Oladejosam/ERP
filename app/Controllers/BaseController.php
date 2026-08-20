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

    protected function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    protected function requireCompanyModule(string $moduleKey): void
    {
        $this->requireAccess();
        if (!(new CompanyModel())->hasModuleAccess($moduleKey)) {
            $_SESSION['company_flash'] = 'This module is not enabled for the selected company.';
            $this->redirect('/');
        }
    }
}
