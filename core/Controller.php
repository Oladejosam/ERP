<?php
/**
 * Base controller for the ERP MVC structure.
 */
declare(strict_types=1);

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = APP_ROOT . '/app/Views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new RuntimeException("View not found: {$view}");
        }
        require $viewFile;
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . BASE_URL . $url);
        exit;
    }

    protected function json(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
