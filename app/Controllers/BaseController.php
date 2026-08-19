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
