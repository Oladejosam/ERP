<?php
/**
 * Home controller for dashboard and public pages.
 */
declare(strict_types=1);

require_once APP_ROOT . '/app/Controllers/BaseController.php';
require_once APP_ROOT . '/app/Models/CompanyModel.php';

class HomeController extends BaseController
{
    public function index(): void
    {
        $companySettings = (new CompanyModel())->getSettings();
        if (trim((string)($companySettings['company_name'] ?? '')) === '') {
            $this->redirect('/setup');
        }

        $this->requireAccess();
        $portalRoute = $this->portalRouteForRole();
        if ($portalRoute !== '/') {
            $this->redirect($portalRoute);
        }

        $pdo = Database::getInstance();
        $companyId = max(1, (int)($_SESSION['selected_company_id'] ?? 1));
        $employeeStmt = $pdo->prepare('SELECT COUNT(*) FROM employees WHERE company_id = ?');
        $employeeStmt->execute([$companyId]);
        $employees = (int)$employeeStmt->fetchColumn();
        $inventoryStmt = $pdo->prepare('SELECT COUNT(*) FROM inventory_items WHERE company_id = ?');
        $inventoryStmt->execute([$companyId]);
        $inventoryItems = (int)$inventoryStmt->fetchColumn();
        $invoiceStmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE company_id = ?');
        $invoiceStmt->execute([$companyId]);
        $invoiceTotal = (float)$invoiceStmt->fetchColumn();
        $purchaseStmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount), 0) FROM purchase_orders WHERE company_id = ?');
        $purchaseStmt->execute([$companyId]);
        $purchaseTotal = (float)$purchaseStmt->fetchColumn();

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'companyName' => (string)($companySettings['company_name'] ?? ''),
            'employees' => $employees,
            'inventory_items' => $inventoryItems,
            'revenue' => $invoiceTotal,
            'expenses' => $purchaseTotal,
            'profit' => max(0.0, $invoiceTotal - $purchaseTotal),
        ]);
    }

    private function portalRouteForRole(): string
    {
        $roleName = strtolower(trim((string)Auth::userRoleName()));
        if ($roleName === 'staff') {
            return '/portal/staff';
        }
        if ($roleName === 'managing director' || $roleName === 'managing_director') {
            return '/portal/managing-director';
        }
        if ($roleName === 'super admin' || $roleName === 'superadministrator' || $roleName === 'super administrator') {
            return '/portal/super-admin';
        }
        if ($roleName === 'admin') {
            return '/portal/admin';
        }
        if ($roleName === 'finance manager' || $roleName === 'finance_manager') {
            return '/portal/finance-manager';
        }
        if ($roleName === 'hr manager' || $roleName === 'hr_manager') {
            return '/portal/hr-manager';
        }
        if ($roleName === 'site engineer' || $roleName === 'site_engineer') {
            return '/portal/site-engineer';
        }
        if ($roleName === 'department head' || $roleName === 'head of department' || $roleName === 'dept head') {
            return '/portal/department-head';
        }
        if ($roleName === 'logistics officer' || $roleName === 'logistics_officer') {
            return '/portal/logistics-officer';
        }
        if ($roleName === 'hr') {
            return '/portal/hr';
        }
        if ($roleName === 'accountant') {
            return '/portal/accountant';
        }
        if ($roleName === 'procurement officer' || $roleName === 'procurement_officer') {
            return '/portal/procurement-officer';
        }
        return '/';
    }

    public function login(): void
    {
        $this->view('auth/login', ['title' => 'Login']);
    }

    public function notFound(): void
    {
        http_response_code(404);
        echo '404 - Page not found';
    }
}
