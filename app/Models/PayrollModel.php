<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Model.php';
require_once APP_ROOT . '/app/Models/EmployeeModel.php';

class PayrollModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensurePayrollTable();
    }

    private function ensurePayrollTable(): void
    {
        $this->query('CREATE TABLE IF NOT EXISTS payrolls (id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL DEFAULT 1, employee_id INT NOT NULL, payroll_month VARCHAR(20) NOT NULL, basic_salary DECIMAL(12,2) NOT NULL, allowances DECIMAL(12,2) DEFAULT 0.00, deductions DECIMAL(12,2) DEFAULT 0.00, net_pay DECIMAL(12,2) NOT NULL, sent_to_portal TINYINT(1) DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY (employee_id) REFERENCES employees(id))');
    }

    public function getEmployees(): array
    {
        $stmt = $this->query('SELECT * FROM employees WHERE company_id = ? ORDER BY first_name ASC, last_name ASC', [$this->currentCompanyId()]);
        return $stmt->fetchAll();
    }

    public function getPayrolls(): array
    {
        $stmt = $this->query('SELECT p.*, e.first_name, e.last_name, e.position FROM payrolls p LEFT JOIN employees e ON e.id = p.employee_id WHERE p.company_id = ? ORDER BY p.created_at DESC', [$this->currentCompanyId()]);
        return $stmt->fetchAll();
    }

    public function getPortalPayrolls(): array
    {
        $stmt = $this->query('SELECT p.*, e.first_name, e.last_name, e.position FROM payrolls p LEFT JOIN employees e ON e.id = p.employee_id WHERE p.company_id = ? AND p.sent_to_portal = 1 ORDER BY p.created_at DESC', [$this->currentCompanyId()]);
        return $stmt->fetchAll();
    }

    public function savePayroll(array $data): int
    {
        $employeeId = (int)($data['employee_id'] ?? 0);
        $month = trim((string)($data['payroll_month'] ?? '')) ?: date('Y-m');
        $basic = (float)($data['basic_salary'] ?? 0.0);
        $allowances = (float)($data['allowances'] ?? 0.0);
        $deductions = (float)($data['deductions'] ?? 0.0);
        $net = $basic + $allowances - $deductions;

        if ($employeeId <= 0) {
            throw new InvalidArgumentException('Employee is required.');
        }

        $this->query(
            'INSERT INTO payrolls (company_id, employee_id, payroll_month, basic_salary, allowances, deductions, net_pay, sent_to_portal, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())',
            [$this->currentCompanyId(), $employeeId, $month, number_format($basic, 2, '.', ''), number_format($allowances, 2, '.', ''), number_format($deductions, 2, '.', ''), number_format($net, 2, '.', '')]
        );

        return (int)$this->db->lastInsertId();
    }

    public function updatePayroll(int $id, array $data): bool
    {
        $employeeId = (int)($data['employee_id'] ?? 0);
        $month = trim((string)($data['payroll_month'] ?? '')) ?: date('Y-m');
        $basic = (float)($data['basic_salary'] ?? 0.0);
        $allowances = (float)($data['allowances'] ?? 0.0);
        $deductions = (float)($data['deductions'] ?? 0.0);
        $net = $basic + $allowances - $deductions;

        $this->query(
            'UPDATE payrolls SET employee_id = ?, payroll_month = ?, basic_salary = ?, allowances = ?, deductions = ?, net_pay = ? WHERE id = ?',
            [$employeeId, $month, number_format($basic, 2, '.', ''), number_format($allowances, 2, '.', ''), number_format($deductions, 2, '.', ''), number_format($net, 2, '.', ''), $id]
        );

        return true;
    }

    public function markPayrollSent(int $id): void
    {
        $this->query('UPDATE payrolls SET sent_to_portal = 1 WHERE id = ? AND company_id = ?', [$id, $this->currentCompanyId()]);
    }

    public function markAllPayrollsSent(): int
    {
        $this->query('UPDATE payrolls SET sent_to_portal = 1 WHERE company_id = ? AND sent_to_portal = 0', [$this->currentCompanyId()]);
        $stmt = $this->query('SELECT ROW_COUNT() AS sent_count');
        $row = $stmt->fetch();
        return (int)($row['sent_count'] ?? 0);
    }

    public function bulkUploadPayrolls(array $file): int
    {
        if (empty($file['tmp_name'])) {
            return 0;
        }

        $path = $file['tmp_name'];
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return 0;
        }

        $count = 0;
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 5) {
                continue;
            }
            $employeeCode = trim((string)($row[0] ?? ''));
            $month = trim((string)($row[1] ?? '')) ?: date('Y-m');
            $basic = (float)($row[2] ?? 0.0);
            $allowances = (float)($row[3] ?? 0.0);
            $deductions = (float)($row[4] ?? 0.0);

            $employee = (new EmployeeModel())->getEmployeeByCode($employeeCode);
            if (!$employee) {
                continue;
            }

            $this->savePayroll([
                'employee_id' => (int)$employee['id'],
                'payroll_month' => $month,
                'basic_salary' => $basic,
                'allowances' => $allowances,
                'deductions' => $deductions,
            ]);
            $count++;
        }
        fclose($handle);
        return $count;
    }
}
