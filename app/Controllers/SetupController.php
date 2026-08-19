<?php
declare(strict_types=1);

require_once APP_ROOT . '/app/Controllers/BaseController.php';
require_once APP_ROOT . '/app/Models/CompanyModel.php';

class SetupController extends BaseController
{
    private CompanyModel $companyModel;

    public function __construct()
    {
        $this->companyModel = new CompanyModel();
    }

    public function index(): void
    {
        $settings = $this->companyModel->getSettings();
        $isRegistered = trim((string)($settings['company_name'] ?? '')) !== '';
        $isNewCompany = !empty($_GET['new']);

        if ($isRegistered && empty($_SESSION['user'])) {
            $this->redirect('/login');
        }
        if ($isRegistered && !empty($_SESSION['user']) && !$this->isSuperAdmin()) {
            $this->redirect('/');
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($isRegistered || $isNewCompany) {
                $this->requireAccess();
                if (!$this->isSuperAdmin()) {
                    $this->redirect('/');
                }
            }

            $companyName = trim((string)($_POST['company_name'] ?? ''));
            $themeColor = trim((string)($_POST['theme_color'] ?? '#1d4ed8'));
            if ($companyName === '') {
                $error = 'Company name is required.';
            } elseif (!preg_match('/^#[0-9a-fA-F]{6}$/', $themeColor)) {
                $error = 'Choose a valid theme color.';
            } else {
                try {
                    $logoPath = $this->handleLogoUpload($_FILES['company_logo'] ?? null);
                    if ($isNewCompany) {
                        $this->companyModel->createCompany($companyName, strtolower($themeColor), $logoPath);
                    } else {
                        $this->companyModel->saveSettings($companyName, strtolower($themeColor), $logoPath);
                    }
                    $_SESSION['company_flash'] = 'Company settings saved successfully.';
                    $this->redirect(!empty($_SESSION['user']) ? '/' : '/login');
                } catch (Throwable $exception) {
                    $error = 'Unable to save company settings: ' . $exception->getMessage();
                }
            }
            $settings['company_name'] = $companyName;
            $settings['theme_color'] = $themeColor;
        }

        $this->view('setup/index', [
            'title' => $isNewCompany ? 'Register Company' : ($isRegistered ? 'Company Settings' : 'Register Company'),
            'settings' => $settings,
            'error' => $error,
            'isRegistered' => $isRegistered,
            'isNewCompany' => $isNewCompany,
        ]);
    }

    public function selectCompany(): void
    {
        $this->requireAccess();
        $role = strtolower(trim((string)($_SESSION['user']['role_name'] ?? '')));
        if (!in_array($role, ['super admin', 'superadministrator', 'super administrator'], true)) {
            $this->redirect('/');
        }
        $companyId = (int)($_POST['company_id'] ?? 0);
        if (!$this->companyModel->selectCompany($companyId)) {
            $_SESSION['company_flash'] = 'The selected company is not available.';
        }
        $this->redirect('/');
    }

    private function isSuperAdmin(): bool
    {
        return in_array(strtolower(trim((string)($_SESSION['user']['role_name'] ?? ''))), ['super admin', 'superadministrator', 'super administrator'], true);
    }

    private function handleLogoUpload(?array $file): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The company logo upload failed.');
        }
        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new RuntimeException('The company logo must be 2 MB or smaller.');
        }

        $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('Use a JPG, PNG, or WEBP logo.');
        }

        $detectedType = (new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($detectedType, $allowedTypes, true)) {
            throw new RuntimeException('The uploaded logo is not a valid image.');
        }

        $uploadDir = APP_ROOT . '/public/uploads/company';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('The company logo directory could not be created.');
        }

        $storedName = 'company_logo_' . bin2hex(random_bytes(12)) . '.' . $extension;
        if (!move_uploaded_file((string)$file['tmp_name'], $uploadDir . '/' . $storedName)) {
            throw new RuntimeException('The company logo could not be saved.');
        }

        return 'company/' . $storedName;
    }
}
