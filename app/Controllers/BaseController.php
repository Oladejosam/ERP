<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Controller.php';

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
}
