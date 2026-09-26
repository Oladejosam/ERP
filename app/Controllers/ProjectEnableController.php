<?php
namespace App\Controllers;

use App\Models\Document;
use PDO;

class ProjectEnableController {
    protected PDO $db;
    protected Document $documentModel;
    protected string $uploadsDir;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->documentModel = new Document($db);
        $this->uploadsDir = dirname(__DIR__, 2) . '/public/uploads/documents';
        if (!is_dir($this->uploadsDir)) {
            mkdir($this->uploadsDir, 0755, true);
        }
    }

    public function index(): void {
        $this->requireAuth();
        $companyId = $_SESSION['company_id'] ?? 1;
        $projectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;

        $documents = $this->documentModel->getByProject($projectId, $companyId);
        
        $stmt = $this->db->prepare("SELECT id, name, project_number, site_location, progress_percent, contract_value FROM projects WHERE company_id = :cid");
        $stmt->execute([':cid' => $companyId]);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require dirname(__DIR__) . '/Views/project_enable/index.php';
    }

    public function upload(): void {
        $this->requireAuth();
        $companyId = $_SESSION['company_id'] ?? 1;
        $userId = $_SESSION['user_id'] ?? 1;

        $projectId = (int)($_POST['project_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'Other');

        if (empty($_FILES['document_file']['name'])) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Please select a file to upload.'];
            header("Location: /modules/project-enable" . ($projectId ? "?project_id={$projectId}" : ""));
            exit;
        }

        $file = $_FILES['document_file'];
        $origName = basename($file['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        // Security: Disallow executable extensions
        $disallowed = ['php', 'phtml', 'php3', 'phar', 'exe', 'sh', 'bat', 'cmd', 'pl', 'cgi', 'js'];
        if (in_array($ext, $disallowed, true)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Executable files (.php, .exe, .sh, etc.) are strictly prohibited.'];
            header("Location: /modules/project-enable?project_id={$projectId}");
            exit;
        }

        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'png', 'jpg', 'jpeg', 'webp', 'txt', 'dwg'];
        if (!in_array($ext, $allowed, true)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => "File format .{$ext} is not allowed."];
            header("Location: /modules/project-enable?project_id={$projectId}");
            exit;
        }

        if ($file['size'] > 25 * 1024 * 1024) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'File size exceeds maximum limit of 25MB.'];
            header("Location: /modules/project-enable?project_id={$projectId}");
            exit;
        }

        // Generate safe randomized filename
        $safeName = 'doc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destination = $this->uploadsDir . '/' . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to save uploaded file.'];
            header("Location: /modules/project-enable?project_id={$projectId}");
            exit;
        }

        $this->documentModel->create([
            'title' => $title ?: $origName,
            'file_name' => $safeName,
            'category' => $category,
            'related_type' => 'project_enable',
            'related_id' => $projectId,
            'uploaded_by' => $userId
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => "Document '{$title}' uploaded successfully."];
        header("Location: /modules/project-enable?project_id={$projectId}");
        exit;
    }

    public function view(int $id): void {
        $this->requireAuth();
        $companyId = $_SESSION['company_id'] ?? 1;
        $doc = $this->documentModel->getById($id, $companyId);

        if (!$doc) {
            http_response_code(404);
            echo "Document not found or access denied.";
            exit;
        }

        $safeName = basename($doc['file_name']);
        $filePath = $this->uploadsDir . '/' . $safeName;

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "Physical file missing from server storage.";
            exit;
        }

        $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
        $contentTypes = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'txt' => 'text/plain'
        ];

        header('Content-Type: ' . ($contentTypes[$ext] ?? 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . rawurlencode($doc['title']) . '"');
        readfile($filePath);
        exit;
    }

    public function download(int $id): void {
        $this->requireAuth();
        $companyId = $_SESSION['company_id'] ?? 1;
        $doc = $this->documentModel->getById($id, $companyId);

        if (!$doc) {
            http_response_code(404);
            echo "Document not found or access denied.";
            exit;
        }

        $safeName = basename($doc['file_name']);
        $filePath = $this->uploadsDir . '/' . $safeName;

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "Physical file missing.";
            exit;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . rawurlencode($doc['title']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function delete(int $id): void {
        $this->requireAuth();
        $companyId = $_SESSION['company_id'] ?? 1;
        $doc = $this->documentModel->getById($id, $companyId);

        if (!$doc) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Document not found or access denied.'];
            header("Location: /modules/project-enable");
            exit;
        }

        $filePath = $this->uploadsDir . '/' . basename($doc['file_name']);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->documentModel->delete($id);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Document deleted successfully.'];
        header("Location: /modules/project-enable?project_id=" . ($doc['related_id'] ?? ''));
        exit;
    }

    protected function requireAuth(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
}
