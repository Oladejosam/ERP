<?php
declare(strict_types=1);

require_once APP_ROOT . '/app/Controllers/BaseController.php';
require_once APP_ROOT . '/app/Models/CompanyModel.php';

class CompanyController extends BaseController
{
    private CompanyModel $companyModel;

    public function __construct()
    {
        $this->companyModel = new CompanyModel();
    }

    public function workspace(): void
    {
        $this->requireSuperAdmin();
        $this->view('company/workspace', [
            'title' => 'Select Company',
            'companies' => $this->companyModel->getCompanies(true),
            'modules' => CompanyModel::availableModules(),
            'selectedCompanyId' => (int)($_SESSION['selected_company_id'] ?? 0),
            'error' => '',
        ]);
    }

    public function select(): void
    {
        $this->requireSuperAdmin();
        $companyId = (int)($_POST['company_id'] ?? 0);
        if (!$this->companyModel->selectCompany($companyId)) {
            $this->setWorkspaceError('The selected company is not available.');
            return;
        }
        $this->companyModel->saveModuleAccess($companyId, (array)($_POST['modules'] ?? []));
        $this->redirect('/');
    }

    public function setActive(): void
    {
        $this->requireSuperAdmin();
        $companyId = (int)($_POST['company_id'] ?? 0);
        $active = (string)($_POST['active'] ?? '0') === '1';

        try {
            $this->companyModel->setCompanyActive($companyId, $active);
            if (!$active && (int)($_SESSION['selected_company_id'] ?? 0) === $companyId) {
                unset($_SESSION['selected_company_id']);
            }
            $_SESSION['company_flash'] = $active ? 'Company enabled successfully.' : 'Company disabled successfully. Its data was preserved.';
        } catch (Throwable $exception) {
            $_SESSION['company_flash'] = 'Unable to update company status: ' . $exception->getMessage();
        }
        $this->redirect('/company/workspace');
    }

    public function create(): void
    {
        $this->requireSuperAdmin();
        $companyName = trim((string)($_POST['company_name'] ?? ''));
        $themeColor = trim((string)($_POST['theme_color'] ?? '#1d4ed8'));
        if ($companyName === '' || !preg_match('/^#[0-9a-fA-F]{6}$/', $themeColor)) {
            $this->setWorkspaceError('Company name and a valid theme color are required.');
            return;
        }

        try {
            $logoPath = $this->handleLogoUpload($_FILES['company_logo'] ?? null);
            $companyId = $this->companyModel->createCompany($companyName, strtolower($themeColor), $logoPath);
            $this->companyModel->saveModuleAccess($companyId, (array)($_POST['modules'] ?? []));
            $_SESSION['company_flash'] = 'Company created. Select its modules were saved.';
            $this->redirect('/company/workspace');
        } catch (Throwable $exception) {
            $this->setWorkspaceError('Unable to create company: ' . $exception->getMessage());
        }
    }

    private function setWorkspaceError(string $error): void
    {
        $this->view('company/workspace', [
            'title' => 'Select Company',
            'companies' => $this->companyModel->getCompanies(true),
            'modules' => CompanyModel::availableModules(),
            'selectedCompanyId' => (int)($_SESSION['selected_company_id'] ?? 0),
            'error' => $error,
        ]);
    }

    private function requireSuperAdmin(): void
    {
        $this->requireAccess();
        $role = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        if (!in_array($role, ['super admin', 'superadministrator', 'super administrator'], true)) {
            $this->redirect('/');
        }
    }

    private function handleLogoUpload(?array $file): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new RuntimeException('The company logo must be a valid image of 2 MB or smaller.');
        }
        $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new RuntimeException('Use a JPG, PNG, or WEBP logo.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('The uploaded logo is not a valid image.');
        }
        $directory = APP_ROOT . '/public/uploads/company';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('The company logo directory could not be created.');
        }
        $fileName = 'company_logo_' . bin2hex(random_bytes(12)) . '.' . $extension;
        if (!move_uploaded_file((string)$file['tmp_name'], $directory . '/' . $fileName)) {
            throw new RuntimeException('The company logo could not be saved.');
        }
        return 'company/' . $fileName;
    }
}
