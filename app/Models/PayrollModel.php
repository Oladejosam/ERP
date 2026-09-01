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
        
        $this->query(
            'CREATE TABLE IF NOT EXISTS salary_structures (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                employee_id INT NOT NULL,
                basic_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                house_allowance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                transport_allowance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                meal_allowance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                other_allowances DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                pension_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                insurance_deduction DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                effective_from DATE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_employee_structure (company_id, employee_id),
                FOREIGN KEY (employee_id) REFERENCES employees(id),
                INDEX idx_salary_structures_company (company_id)
            )'
        );

        $this->query(
            'CREATE TABLE IF NOT EXISTS role_salary_structures (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                role_id INT NOT NULL,
                basic_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                house_allowance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                transport_allowance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                meal_allowance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                other_allowances DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                pension_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                insurance_deduction DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                effective_from DATE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_role_structure (company_id, role_id),
                FOREIGN KEY (role_id) REFERENCES roles(id),
                INDEX idx_role_salary_structures_company (company_id)
            )'
        );
        
        $this->query(
            'CREATE TABLE IF NOT EXISTS salary_advances (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                employee_id INT NOT NULL,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                status ENUM("pending", "approved", "rejected", "recovered") NOT NULL DEFAULT "pending",
                requested_date DATE NOT NULL,
                approved_date DATE NULL,
                approved_by INT NULL,
                recovery_deduction DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                recovered_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                remarks TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_salary_advances_employee (employee_id),
                INDEX idx_salary_advances_company (company_id),
                FOREIGN KEY (employee_id) REFERENCES employees(id),
                FOREIGN KEY (approved_by) REFERENCES users(id)
            )'
        );
        
        $this->query(
            'CREATE TABLE IF NOT EXISTS employee_loans (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                employee_id INT NOT NULL,
                loan_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                interest_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                tenure_months INT NOT NULL DEFAULT 12,
                emi_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                status ENUM("active", "closed", "defaulted") NOT NULL DEFAULT "active",
                disbursed_date DATE NULL,
                due_date DATE NULL,
                balance_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                no_of_installments_completed INT NOT NULL DEFAULT 0,
                remarks TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_employee_loans_employee (employee_id),
                INDEX idx_employee_loans_company (company_id),
                FOREIGN KEY (employee_id) REFERENCES employees(id)
            )'
        );
        
        $this->query(
            'CREATE TABLE IF NOT EXISTS loan_installments (
                id INT PRIMARY KEY AUTO_INCREMENT,
                loan_id INT NOT NULL,
                installment_number INT NOT NULL,
                due_date DATE NOT NULL,
                emi_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                principal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                interest DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                status ENUM("pending", "paid", "defaulted") NOT NULL DEFAULT "pending",
                paid_date DATE NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_loan_installment (loan_id, installment_number),
                INDEX idx_loan_installments_loan (loan_id),
                FOREIGN KEY (loan_id) REFERENCES employee_loans(id) ON DELETE CASCADE
            )'
        );
        
        $this->query(
            'CREATE TABLE IF NOT EXISTS payroll_components (
                id INT PRIMARY KEY AUTO_INCREMENT,
                payroll_id INT NOT NULL,
                component_type ENUM("allowance", "deduction") NOT NULL,
                component_name VARCHAR(100) NOT NULL,
                component_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                remarks VARCHAR(255) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_payroll_components_payroll (payroll_id),
                FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE
            )'
        );
        
        $this->query(
            'CREATE TABLE IF NOT EXISTS payslips (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL,
                payroll_id INT NOT NULL,
                employee_id INT NOT NULL,
                payroll_month VARCHAR(20) NOT NULL,
                gross_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                total_allowances DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                total_deductions DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                net_pay DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                pdf_path VARCHAR(255) NULL,
                generated_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_payslips_employee (employee_id),
                INDEX idx_payslips_company (company_id),
                INDEX idx_payslips_month (payroll_month),
                FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE,
                FOREIGN KEY (employee_id) REFERENCES employees(id)
            )'
        );
        
        $this->query(
            'CREATE TABLE IF NOT EXISTS payroll_configurations (
                id INT PRIMARY KEY AUTO_INCREMENT,
                company_id INT NOT NULL UNIQUE,
                financial_year_start_month INT NOT NULL DEFAULT 1,
                tax_calculation_method ENUM("percentage", "slab") NOT NULL DEFAULT "percentage",
                default_tax_rate DECIMAL(5,2) NOT NULL DEFAULT 10.00,
                default_pension_rate DECIMAL(5,2) NOT NULL DEFAULT 8.00,
                max_salary_advance_percentage INT NOT NULL DEFAULT 50,
                allow_multiple_loans TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES companies(id)
            )'
        );
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

    // ========== SALARY STRUCTURE METHODS ==========
    
    public function saveSalaryStructure(int $employeeId, array $data): void
    {
        $companyId = $this->currentCompanyId();
        $effectiveFrom = trim((string)($data['effective_from'] ?? date('Y-m-d')));
        
        $this->query(
            'INSERT INTO salary_structures (company_id, employee_id, basic_salary, house_allowance, transport_allowance, meal_allowance, other_allowances, tax_rate, pension_rate, insurance_deduction, effective_from) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            basic_salary = VALUES(basic_salary),
            house_allowance = VALUES(house_allowance),
            transport_allowance = VALUES(transport_allowance),
            meal_allowance = VALUES(meal_allowance),
            other_allowances = VALUES(other_allowances),
            tax_rate = VALUES(tax_rate),
            pension_rate = VALUES(pension_rate),
            insurance_deduction = VALUES(insurance_deduction),
            effective_from = VALUES(effective_from),
            updated_at = NOW()',
            [
                $companyId,
                $employeeId,
                (float)($data['basic_salary'] ?? 0),
                (float)($data['house_allowance'] ?? 0),
                (float)($data['transport_allowance'] ?? 0),
                (float)($data['meal_allowance'] ?? 0),
                (float)($data['other_allowances'] ?? 0),
                (float)($data['tax_rate'] ?? 0),
                (float)($data['pension_rate'] ?? 0),
                (float)($data['insurance_deduction'] ?? 0),
                $effectiveFrom
            ]
        );
    }

    public function saveRoleSalaryStructure(int $roleId, array $data): void
    {
        $companyId = $this->currentCompanyId();
        $effectiveFrom = trim((string)($data['effective_from'] ?? date('Y-m-d')));

        $this->query(
            'INSERT INTO role_salary_structures (company_id, role_id, basic_salary, house_allowance, transport_allowance, meal_allowance, other_allowances, tax_rate, pension_rate, insurance_deduction, effective_from)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            basic_salary = VALUES(basic_salary),
            house_allowance = VALUES(house_allowance),
            transport_allowance = VALUES(transport_allowance),
            meal_allowance = VALUES(meal_allowance),
            other_allowances = VALUES(other_allowances),
            tax_rate = VALUES(tax_rate),
            pension_rate = VALUES(pension_rate),
            insurance_deduction = VALUES(insurance_deduction),
            effective_from = VALUES(effective_from),
            updated_at = NOW()',
            [
                $companyId,
                $roleId,
                (float)($data['basic_salary'] ?? 0),
                (float)($data['house_allowance'] ?? 0),
                (float)($data['transport_allowance'] ?? 0),
                (float)($data['meal_allowance'] ?? 0),
                (float)($data['other_allowances'] ?? 0),
                (float)($data['tax_rate'] ?? 0),
                (float)($data['pension_rate'] ?? 0),
                (float)($data['insurance_deduction'] ?? 0),
                $effectiveFrom,
            ]
        );
    }

    public function getSalaryStructure(int $employeeId): ?array
    {
        $stmt = $this->query(
            'SELECT * FROM salary_structures WHERE company_id = ? AND employee_id = ? LIMIT 1',
            [$this->currentCompanyId(), $employeeId]
        );
        return $stmt->fetch() ?: null;
    }

    public function getRoleSalaryStructure(int $roleId): ?array
    {
        $stmt = $this->query(
            'SELECT rss.*, r.name AS role_name FROM role_salary_structures rss
            INNER JOIN roles r ON r.id = rss.role_id
            WHERE rss.company_id = ? AND rss.role_id = ? LIMIT 1',
            [$this->currentCompanyId(), $roleId]
        );
        return $stmt->fetch() ?: null;
    }

    public function getEffectiveSalaryStructure(int $employeeId): ?array
    {
        $structure = $this->getSalaryStructure($employeeId);
        if ($structure) {
            return $structure;
        }

        $roleId = (int)$this->query(
            'SELECT role_id FROM users WHERE employee_id = ? AND company_id = ? ORDER BY id DESC LIMIT 1',
            [$employeeId, $this->currentCompanyId()]
        )->fetchColumn();

        if ($roleId <= 0) {
            return null;
        }

        return $this->getRoleSalaryStructure($roleId);
    }

    public function getAllSalaryStructures(): array
    {
        return $this->query(
            'SELECT ss.*, e.first_name, e.last_name, e.employee_code, e.position FROM salary_structures ss 
            INNER JOIN employees e ON e.id = ss.employee_id 
            WHERE ss.company_id = ? ORDER BY e.first_name ASC, e.last_name ASC',
            [$this->currentCompanyId()]
        )->fetchAll();
    }

    public function getAllRoleSalaryStructures(): array
    {
        return $this->query(
            'SELECT rss.*, r.name AS role_name FROM role_salary_structures rss
            INNER JOIN roles r ON r.id = rss.role_id
            WHERE rss.company_id = ? ORDER BY r.name ASC',
            [$this->currentCompanyId()]
        )->fetchAll();
    }

    // ========== SALARY ADVANCE METHODS ==========

    public function requestSalaryAdvance(int $employeeId, float $amount, string $remarks = ''): int
    {
        $companyId = $this->currentCompanyId();
        
        $this->query(
            'INSERT INTO salary_advances (company_id, employee_id, amount, status, requested_date, remarks) 
            VALUES (?, ?, ?, "pending", CURDATE(), ?)',
            [$companyId, $employeeId, number_format($amount, 2, '.', ''), $remarks !== '' ? $remarks : null]
        );
        
        return (int)$this->db->lastInsertId();
    }

    public function approveSalaryAdvance(int $advanceId, int $approvedBy): void
    {
        $this->query(
            'UPDATE salary_advances SET status = "approved", approved_date = CURDATE(), approved_by = ? WHERE id = ? AND status = "pending" AND company_id = ?',
            [$approvedBy, $advanceId, $this->currentCompanyId()]
        );
    }

    public function rejectSalaryAdvance(int $advanceId, string $reason = ''): void
    {
        $this->query(
            'UPDATE salary_advances SET status = "rejected" WHERE id = ? AND status = "pending" AND company_id = ?',
            [$advanceId, $this->currentCompanyId()]
        );
    }

    public function getSalaryAdvances(?int $employeeId = null, ?string $status = null): array
    {
        $sql = 'SELECT sa.*, e.first_name, e.last_name, e.employee_code, u.name as approved_by_name 
                FROM salary_advances sa 
                INNER JOIN employees e ON e.id = sa.employee_id 
                LEFT JOIN users u ON u.id = sa.approved_by 
                WHERE sa.company_id = ?';
        $params = [$this->currentCompanyId()];
        
        if ($employeeId !== null && $employeeId > 0) {
            $sql .= ' AND sa.employee_id = ?';
            $params[] = $employeeId;
        }
        
        if ($status !== null && $status !== '') {
            $sql .= ' AND sa.status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY sa.requested_date DESC';
        
        return $this->query($sql, $params)->fetchAll();
    }

    public function getEmployeeSalaryAdvances(int $employeeId): array
    {
        return $this->query(
            'SELECT * FROM salary_advances WHERE company_id = ? AND employee_id = ? ORDER BY requested_date DESC',
            [$this->currentCompanyId(), $employeeId]
        )->fetchAll();
    }

    // ========== EMPLOYEE LOAN METHODS ==========

    public function createEmployeeLoan(int $employeeId, array $data): int
    {
        $companyId = $this->currentCompanyId();
        $loanAmount = (float)($data['loan_amount'] ?? 0);
        $interestRate = (float)($data['interest_rate'] ?? 0);
        $tenureMonths = (int)($data['tenure_months'] ?? 12);
        
        $monthlyRate = ($interestRate / 100) / 12;
        $emiAmount = $monthlyRate > 0 
            ? $loanAmount * ($monthlyRate * pow(1 + $monthlyRate, $tenureMonths)) / (pow(1 + $monthlyRate, $tenureMonths) - 1)
            : $loanAmount / $tenureMonths;
        
        $this->db->beginTransaction();
        try {
            $this->query(
                'INSERT INTO employee_loans (company_id, employee_id, loan_amount, interest_rate, tenure_months, emi_amount, balance_amount, disbursed_date, due_date, remarks) 
                VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? MONTH), ?)',
                [
                    $companyId,
                    $employeeId,
                    number_format($loanAmount, 2, '.', ''),
                    number_format($interestRate, 2, '.', ''),
                    $tenureMonths,
                    number_format($emiAmount, 2, '.', ''),
                    number_format($loanAmount, 2, '.', ''),
                    $tenureMonths,
                    trim((string)($data['remarks'] ?? '')) ?: null
                ]
            );
            
            $loanId = (int)$this->db->lastInsertId();
            
            // Generate EMI schedule
            $this->generateEMISchedule($loanId, $loanAmount, $interestRate, $tenureMonths);
            
            $this->db->commit();
            return $loanId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function generateEMISchedule(int $loanId, float $loanAmount, float $interestRate, int $tenureMonths): void
    {
        $monthlyRate = ($interestRate / 100) / 12;
        $emiAmount = $monthlyRate > 0 
            ? $loanAmount * ($monthlyRate * pow(1 + $monthlyRate, $tenureMonths)) / (pow(1 + $monthlyRate, $tenureMonths) - 1)
            : $loanAmount / $tenureMonths;
        
        $balance = $loanAmount;
        
        for ($i = 1; $i <= $tenureMonths; $i++) {
            $interest = $monthlyRate > 0 ? $balance * $monthlyRate : 0;
            $principal = $emiAmount - $interest;
            $balance -= $principal;
            
            $dueDate = date('Y-m-d', strtotime("+$i months", strtotime(date('Y-m-01'))));
            
            $this->query(
                'INSERT INTO loan_installments (loan_id, installment_number, due_date, emi_amount, principal, interest, status) 
                VALUES (?, ?, ?, ?, ?, ?, "pending")',
                [
                    $loanId,
                    $i,
                    $dueDate,
                    number_format($emiAmount, 2, '.', ''),
                    number_format($principal, 2, '.', ''),
                    number_format($interest, 2, '.', '')
                ]
            );
        }
    }

    public function getEmployeeLoan(int $loanId): ?array
    {
        $loan = $this->query(
            'SELECT el.*, e.first_name, e.last_name, e.employee_code 
            FROM employee_loans el 
            INNER JOIN employees e ON e.id = el.employee_id 
            WHERE el.id = ? AND el.company_id = ? LIMIT 1',
            [$loanId, $this->currentCompanyId()]
        )->fetch();
        
        if (!$loan) return null;
        
        $loan['installments'] = $this->query(
            'SELECT * FROM loan_installments WHERE loan_id = ? ORDER BY installment_number ASC',
            [$loanId]
        )->fetchAll();
        
        return $loan;
    }

    public function getEmployeeLoans(?int $employeeId = null, ?string $status = null): array
    {
        $sql = 'SELECT el.*, e.first_name, e.last_name, e.employee_code 
                FROM employee_loans el 
                INNER JOIN employees e ON e.id = el.employee_id 
                WHERE el.company_id = ?';
        $params = [$this->currentCompanyId()];
        
        if ($employeeId !== null && $employeeId > 0) {
            $sql .= ' AND el.employee_id = ?';
            $params[] = $employeeId;
        }
        
        if ($status !== null && $status !== '') {
            $sql .= ' AND el.status = ?';
            $params[] = $status;
        }
        
        $sql .= ' ORDER BY el.created_at DESC';
        
        return $this->query($sql, $params)->fetchAll();
    }

    public function markLoanInstallmentPaid(int $installmentId): void
    {
        $this->db->beginTransaction();
        try {
            $this->query(
                'UPDATE loan_installments SET status = "paid", paid_date = CURDATE() WHERE id = ? AND status = "pending"',
                [$installmentId]
            );
            
            $installment = $this->query('SELECT loan_id FROM loan_installments WHERE id = ?', [$installmentId])->fetch();
            if ($installment) {
                $loanId = (int)$installment['loan_id'];
                $loan = $this->query('SELECT * FROM employee_loans WHERE id = ?', [$loanId])->fetch();
                
                if ($loan) {
                    $paidInstallments = $this->query(
                        'SELECT COUNT(*) as count FROM loan_installments WHERE loan_id = ? AND status = "paid"',
                        [$loanId]
                    )->fetch();
                    
                    $totalInstallments = $this->query(
                        'SELECT COUNT(*) as count FROM loan_installments WHERE loan_id = ?',
                        [$loanId]
                    )->fetch();
                    
                    $tenureMonths = (int)$loan['tenure_months'];
                    $loanAmount = (float)$loan['loan_amount'];
                    
                    $totalPaid = (float)($paidInstallments['count'] ?? 0) * ((float)$loan['emi_amount']);
                    $newBalance = max(0, $loanAmount - $totalPaid);
                    
                    $this->query(
                        'UPDATE employee_loans SET balance_amount = ?, no_of_installments_completed = ? WHERE id = ?',
                        [number_format($newBalance, 2, '.', ''), (int)$paidInstallments['count'], $loanId]
                    );
                    
                    if ((int)$paidInstallments['count'] === $tenureMonths) {
                        $this->query('UPDATE employee_loans SET status = "closed" WHERE id = ?', [$loanId]);
                    }
                }
            }
            
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // ========== PAYROLL CALCULATION METHODS ==========

    public function calculatePayroll(int $employeeId, string $payrollMonth): array
    {
        $companyId = $this->currentCompanyId();
        
        $structure = $this->getEffectiveSalaryStructure($employeeId);
        if (!$structure) {
            throw new InvalidArgumentException('No salary structure found for employee or role');
        }
        
        // Calculate allowances
        $basicSalary = (float)$structure['basic_salary'];
        $houseAllowance = (float)$structure['house_allowance'];
        $transportAllowance = (float)$structure['transport_allowance'];
        $mealAllowance = (float)$structure['meal_allowance'];
        $otherAllowances = (float)$structure['other_allowances'];
        
        $grossSalary = $basicSalary + $houseAllowance + $transportAllowance + $mealAllowance + $otherAllowances;
        $totalAllowances = $grossSalary - $basicSalary;
        
        // Calculate deductions
        $taxRate = (float)$structure['tax_rate'];
        $pensionRate = (float)$structure['pension_rate'];
        $insuranceDeduction = (float)$structure['insurance_deduction'];
        
        $incomeTax = ($taxRate / 100) * $grossSalary;
        $pensionContribution = ($pensionRate / 100) * $grossSalary;
        
        $totalDeductions = $incomeTax + $pensionContribution + $insuranceDeduction;
        
        // Add salary advance deductions
        $advances = $this->query(
            'SELECT amount FROM salary_advances WHERE company_id = ? AND employee_id = ? AND status = "approved" AND recovery_deduction = 0 LIMIT 1',
            [$companyId, $employeeId]
        )->fetch();
        
        if ($advances) {
            $totalDeductions += (float)$advances['amount'];
        }
        
        // Add loan EMI deductions
        $activeLoan = $this->query(
            'SELECT emi_amount FROM employee_loans WHERE company_id = ? AND employee_id = ? AND status = "active" LIMIT 1',
            [$companyId, $employeeId]
        )->fetch();
        
        $loanDeduction = 0;
        if ($activeLoan) {
            $loanDeduction = (float)$activeLoan['emi_amount'];
            $totalDeductions += $loanDeduction;
        }
        
        $netPay = $grossSalary - $totalDeductions;
        
        return [
            'employee_id' => $employeeId,
            'payroll_month' => $payrollMonth,
            'basic_salary' => $basicSalary,
            'gross_salary' => $grossSalary,
            'house_allowance' => $houseAllowance,
            'transport_allowance' => $transportAllowance,
            'meal_allowance' => $mealAllowance,
            'other_allowances' => $otherAllowances,
            'total_allowances' => $totalAllowances,
            'income_tax' => $incomeTax,
            'pension_contribution' => $pensionContribution,
            'insurance_deduction' => $insuranceDeduction,
            'advance_deduction' => (float)($advances['amount'] ?? 0),
            'loan_deduction' => $loanDeduction,
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay
        ];
    }

    public function processMonthlyPayroll(string $payrollMonth, array $employeeIds = []): array
    {
        $companyId = $this->currentCompanyId();
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        
        if (empty($employeeIds)) {
            $employees = $this->getEmployees();
            $employeeIds = array_column($employees, 'id');
        }
        
        foreach ($employeeIds as $employeeId) {
            try {
                $calculation = $this->calculatePayroll((int)$employeeId, $payrollMonth);
                
                $payrollId = $this->savePayroll([
                    'employee_id' => $calculation['employee_id'],
                    'payroll_month' => $calculation['payroll_month'],
                    'basic_salary' => $calculation['basic_salary'],
                    'allowances' => $calculation['total_allowances'],
                    'deductions' => $calculation['total_deductions']
                ]);
                
                // Save payroll components
                $allowances = [
                    'House Allowance' => $calculation['house_allowance'],
                    'Transport Allowance' => $calculation['transport_allowance'],
                    'Meal Allowance' => $calculation['meal_allowance'],
                    'Other Allowances' => $calculation['other_allowances']
                ];
                
                foreach ($allowances as $name => $value) {
                    if ($value > 0) {
                        $this->query(
                            'INSERT INTO payroll_components (payroll_id, component_type, component_name, component_value) VALUES (?, "allowance", ?, ?)',
                            [$payrollId, $name, number_format($value, 2, '.', '')]
                        );
                    }
                }
                
                $deductions = [
                    'Income Tax' => $calculation['income_tax'],
                    'Pension Contribution' => $calculation['pension_contribution'],
                    'Insurance' => $calculation['insurance_deduction'],
                    'Salary Advance' => $calculation['advance_deduction'],
                    'Loan EMI' => $calculation['loan_deduction']
                ];
                
                foreach ($deductions as $name => $value) {
                    if ($value > 0) {
                        $this->query(
                            'INSERT INTO payroll_components (payroll_id, component_type, component_name, component_value) VALUES (?, "deduction", ?, ?)',
                            [$payrollId, $name, number_format($value, 2, '.', '')]
                        );
                    }
                }
                
                $results['success']++;
            } catch (Throwable $e) {
                $results['failed']++;
                $results['errors'][] = "Employee $employeeId: " . $e->getMessage();
            }
        }
        
        return $results;
    }

    public function getPayrollConfiguration(): ?array
    {
        return $this->query(
            'SELECT * FROM payroll_configurations WHERE company_id = ? LIMIT 1',
            [$this->currentCompanyId()]
        )->fetch() ?: null;
    }

    public function savePayrollConfiguration(array $data): void
    {
        $companyId = $this->currentCompanyId();
        
        $this->query(
            'INSERT INTO payroll_configurations (company_id, financial_year_start_month, tax_calculation_method, default_tax_rate, default_pension_rate, max_salary_advance_percentage, allow_multiple_loans)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            financial_year_start_month = VALUES(financial_year_start_month),
            tax_calculation_method = VALUES(tax_calculation_method),
            default_tax_rate = VALUES(default_tax_rate),
            default_pension_rate = VALUES(default_pension_rate),
            max_salary_advance_percentage = VALUES(max_salary_advance_percentage),
            allow_multiple_loans = VALUES(allow_multiple_loans),
            updated_at = NOW()',
            [
                $companyId,
                (int)($data['financial_year_start_month'] ?? 1),
                (string)($data['tax_calculation_method'] ?? 'percentage'),
                (float)($data['default_tax_rate'] ?? 10),
                (float)($data['default_pension_rate'] ?? 8),
                (int)($data['max_salary_advance_percentage'] ?? 50),
                !empty($data['allow_multiple_loans']) ? 1 : 0
            ]
        );
    }

    // ========== PAYSLIP METHODS ==========

    public function generatePayslip(int $payrollId): int
    {
        $companyId = $this->currentCompanyId();
        
        $payroll = $this->query(
            'SELECT p.*, e.first_name, e.last_name, e.employee_code FROM payrolls p 
            INNER JOIN employees e ON e.id = p.employee_id 
            WHERE p.id = ? AND p.company_id = ? LIMIT 1',
            [$payrollId, $companyId]
        )->fetch();
        
        if (!$payroll) {
            throw new InvalidArgumentException('Payroll record not found.');
        }
        
        $components = $this->query(
            'SELECT * FROM payroll_components WHERE payroll_id = ? ORDER BY component_type DESC',
            [$payrollId]
        )->fetchAll();
        
        $this->query(
            'INSERT INTO payslips (company_id, payroll_id, employee_id, payroll_month, gross_salary, total_allowances, total_deductions, net_pay, generated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE generated_at = NOW()',
            [
                $companyId,
                $payrollId,
                (int)$payroll['employee_id'],
                (string)$payroll['payroll_month'],
                (float)($payroll['basic_salary'] + $payroll['allowances']),
                (float)$payroll['allowances'],
                (float)$payroll['deductions'],
                (float)$payroll['net_pay']
            ]
        );
        
        return (int)$this->db->lastInsertId();
    }

    public function getPayslip(int $payslipId): ?array
    {
        $payslip = $this->query(
            'SELECT ps.*, e.first_name, e.last_name, e.employee_code, e.designation, e.salary, p.basic_salary, p.allowances, p.deductions
            FROM payslips ps
            INNER JOIN employees e ON e.id = ps.employee_id
            LEFT JOIN payrolls p ON p.id = ps.payroll_id
            WHERE ps.id = ? AND ps.company_id = ? LIMIT 1',
            [$payslipId, $this->currentCompanyId()]
        )->fetch();
        
        if (!$payslip) return null;
        
        $payslip['components'] = $this->query(
            'SELECT * FROM payroll_components WHERE payroll_id = ? ORDER BY component_type DESC, component_name ASC',
            [(int)$payslip['payroll_id']]
        )->fetchAll();
        
        return $payslip;
    }

    public function getEmployeePayslips(int $employeeId): array
    {
        return $this->query(
            'SELECT ps.* FROM payslips ps 
            WHERE ps.company_id = ? AND ps.employee_id = ? 
            ORDER BY ps.payroll_month DESC',
            [$this->currentCompanyId(), $employeeId]
        )->fetchAll();
    }

    public function getMonthPayslips(string $month): array
    {
        return $this->query(
            'SELECT ps.*, e.first_name, e.last_name, e.employee_code FROM payslips ps 
            INNER JOIN employees e ON e.id = ps.employee_id
            WHERE ps.company_id = ? AND ps.payroll_month = ? 
            ORDER BY e.first_name ASC, e.last_name ASC',
            [$this->currentCompanyId(), $month]
        )->fetchAll();
    }

    public function generatePayslipHTML(int $payslipId): string
    {
        $payslip = $this->getPayslip($payslipId);
        if (!$payslip) {
            throw new InvalidArgumentException('Payslip not found.');
        }
        
        $allowances = array_filter($payslip['components'], fn($c) => $c['component_type'] === 'allowance');
        $deductions = array_filter($payslip['components'], fn($c) => $c['component_type'] === 'deduction');
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payslip - ' . htmlspecialchars($payslip['payroll_month']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; border: 1px solid #ddd; padding: 30px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0; color: #666; }
        .employee-info { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .info-group { flex: 1; }
        .info-group label { font-weight: bold; display: block; }
        .info-group span { color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: right; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; font-weight: bold; text-align: left; }
        td { text-align: right; }
        .label { text-align: left; }
        .section-total { font-weight: bold; background-color: #f9f9f9; }
        .net-pay { font-weight: bold; font-size: 16px; background-color: #e8f5e9; }
        .footer { margin-top: 30px; text-align: center; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PAYSLIP</h1>
            <p>For the month of ' . htmlspecialchars($payslip['payroll_month']) . '</p>
        </div>
        
        <div class="employee-info">
            <div class="info-group">
                <label>Employee Name:</label>
                <span>' . htmlspecialchars($payslip['first_name'] . ' ' . $payslip['last_name']) . '</span>
            </div>
            <div class="info-group">
                <label>Employee Code:</label>
                <span>' . htmlspecialchars($payslip['employee_code']) . '</span>
            </div>
            <div class="info-group">
                <label>Designation:</label>
                <span>' . htmlspecialchars($payslip['designation'] ?? 'N/A') . '</span>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th class="label">Earnings</th>
                    <th>Amount (₦)</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($allowances as $allowance) {
            $html .= '<tr>';
            $html .= '<td class="label">' . htmlspecialchars($allowance['component_name']) . '</td>';
            $html .= '<td>' . number_format((float)$allowance['component_value'], 2) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '<tr class="section-total">
                    <td class="label">Total Earnings</td>
                    <td>' . number_format((float)$payslip['gross_salary'], 2) . '</td>
                </tr>
            </tbody>
        </table>
        
        <table>
            <thead>
                <tr>
                    <th class="label">Deductions</th>
                    <th>Amount (₦)</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($deductions as $deduction) {
            $html .= '<tr>';
            $html .= '<td class="label">' . htmlspecialchars($deduction['component_name']) . '</td>';
            $html .= '<td>' . number_format((float)$deduction['component_value'], 2) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '<tr class="section-total">
                    <td class="label">Total Deductions</td>
                    <td>' . number_format((float)$payslip['total_deductions'], 2) . '</td>
                </tr>
            </tbody>
        </table>
        
        <table>
            <tbody>
                <tr class="net-pay">
                    <td class="label">NET SALARY</td>
                    <td>' . number_format((float)$payslip['net_pay'], 2) . '</td>
                </tr>
            </tbody>
        </table>
        
        <div class="footer">
            <p>This is a computer-generated document and does not require a signature.</p>
            <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
}
