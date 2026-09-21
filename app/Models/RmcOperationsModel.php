<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Model.php';

class RmcOperationsModel extends Model
{
    private const MODULES = [
        'sales_marketing' => ['table' => 'rmc_sales_orders', 'label' => 'Sales & Marketing'],
        'quality_control' => ['table' => 'rmc_quality_tests', 'label' => 'Quality Control'],
        'workshop_maintenance' => ['table' => 'rmc_maintenance_jobs', 'label' => 'Workshop & Maintenance'],
        'mix_design' => ['table' => 'rmc_mix_designs', 'label' => 'Mix Design'],
        'dispatch' => ['table' => 'rmc_dispatches', 'label' => 'Dispatch Management'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensureTables();
    }

    private function ensureTables(): void
    {
        $this->query('CREATE TABLE IF NOT EXISTS rmc_sales_orders (
            id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, order_number VARCHAR(60) NOT NULL,
            customer_name VARCHAR(150) NOT NULL, concrete_grade VARCHAR(60) NOT NULL, quantity_m3 DECIMAL(12,2) NOT NULL DEFAULT 0,
            delivery_date DATE NULL, amount DECIMAL(14,2) NOT NULL DEFAULT 0, status ENUM("draft", "quoted", "approved", "dispatched", "completed", "cancelled") NOT NULL DEFAULT "draft",
            notes TEXT NULL, created_by INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rmc_sales_order (company_id, order_number), INDEX idx_rmc_sales_status (company_id, status)
        )');
        $this->query('CREATE TABLE IF NOT EXISTS rmc_sales_leads (
            id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, lead_number VARCHAR(60) NOT NULL,
            company_name VARCHAR(150) NOT NULL, contact_name VARCHAR(150) NOT NULL, email VARCHAR(180) NULL,
            phone VARCHAR(60) NULL, source VARCHAR(100) NULL, segment VARCHAR(100) NULL, estimated_value DECIMAL(14,2) NOT NULL DEFAULT 0,
            stage ENUM("new", "qualified", "proposal", "negotiation", "won", "lost") NOT NULL DEFAULT "new",
            next_follow_up DATE NULL, notes TEXT NULL, created_by INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rmc_sales_lead (company_id, lead_number), INDEX idx_rmc_sales_lead_stage (company_id, stage)
        )');
        $this->query('CREATE TABLE IF NOT EXISTS rmc_sales_campaigns (
            id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, campaign_code VARCHAR(60) NOT NULL,
            campaign_name VARCHAR(180) NOT NULL, channel VARCHAR(80) NOT NULL, segment VARCHAR(100) NULL,
            budget DECIMAL(14,2) NOT NULL DEFAULT 0, start_date DATE NOT NULL, end_date DATE NULL,
            status ENUM("planned", "active", "paused", "completed") NOT NULL DEFAULT "planned", notes TEXT NULL,
            created_by INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rmc_campaign (company_id, campaign_code), INDEX idx_rmc_campaign_status (company_id, status)
        )');
        $this->query('CREATE TABLE IF NOT EXISTS rmc_sales_quotes (
            id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, quote_number VARCHAR(60) NOT NULL,
            lead_id INT NULL, customer_name VARCHAR(150) NOT NULL, concrete_grade VARCHAR(60) NOT NULL,
            quantity_m3 DECIMAL(12,2) NOT NULL DEFAULT 0, unit_price DECIMAL(14,2) NOT NULL DEFAULT 0, discount DECIMAL(14,2) NOT NULL DEFAULT 0,
            valid_until DATE NULL, status ENUM("draft", "pending_approval", "sent", "accepted", "rejected", "expired") NOT NULL DEFAULT "draft",
            notes TEXT NULL, created_by INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rmc_quote (company_id, quote_number), INDEX idx_rmc_quote_status (company_id, status)
        )');
        $this->query('CREATE TABLE IF NOT EXISTS rmc_quality_tests (
            id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, test_number VARCHAR(60) NOT NULL,
            batch_reference VARCHAR(80) NOT NULL, test_type VARCHAR(100) NOT NULL, result VARCHAR(120) NOT NULL,
            tested_at DATE NOT NULL, status ENUM("pending", "passed", "failed", "retest") NOT NULL DEFAULT "pending",
            standard VARCHAR(120) NULL, acceptance_criteria VARCHAR(255) NULL, measured_value VARCHAR(120) NULL,
            unit VARCHAR(40) NULL, sample_location VARCHAR(180) NULL, inspector_name VARCHAR(150) NULL,
            evidence_url VARCHAR(255) NULL, notes TEXT NULL, created_by INT NULL, reviewed_by INT NULL, reviewed_at DATETIME NULL,
            mix_design_id INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rmc_quality_test (company_id, test_number), INDEX idx_rmc_quality_status (company_id, status)
        )');
        foreach (['standard VARCHAR(120) NULL', 'acceptance_criteria VARCHAR(255) NULL', 'measured_value VARCHAR(120) NULL', 'unit VARCHAR(40) NULL', 'sample_location VARCHAR(180) NULL', 'inspector_name VARCHAR(150) NULL', 'evidence_url VARCHAR(255) NULL', 'reviewed_by INT NULL', 'reviewed_at DATETIME NULL', 'mix_design_id INT NULL'] as $column) {
            $columnName = explode(' ', $column, 2)[0];
            $this->query('ALTER TABLE rmc_quality_tests ADD COLUMN IF NOT EXISTS ' . $columnName . ' ' . substr($column, strlen($columnName) + 1));
        }
        $this->query('CREATE TABLE IF NOT EXISTS rmc_qc_plans (
            id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, plan_number VARCHAR(60) NOT NULL,
            title VARCHAR(180) NOT NULL, project_reference VARCHAR(100) NULL, standard VARCHAR(120) NULL,
            responsible_person VARCHAR(150) NULL, checklist TEXT NULL, status ENUM("draft", "active", "closed") NOT NULL DEFAULT "draft",
            created_by INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rmc_qc_plan (company_id, plan_number), INDEX idx_rmc_qc_plan_status (company_id, status)
        )');
        $this->query('CREATE TABLE IF NOT EXISTS rmc_qc_inspections (
            id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, plan_id INT NULL, inspection_number VARCHAR(60) NOT NULL,
            inspection_type VARCHAR(120) NOT NULL, location VARCHAR(180) NOT NULL, inspected_at DATE NOT NULL,
            inspector_name VARCHAR(150) NOT NULL, status ENUM("pending", "passed", "conditional", "failed") NOT NULL DEFAULT "pending",
            observations TEXT NULL, evidence_url VARCHAR(255) NULL, created_by INT NULL, approved_by INT NULL, approved_at DATETIME NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rmc_qc_inspection (company_id, inspection_number), INDEX idx_rmc_qc_inspection_status (company_id, status)
        )');
        $this->query('CREATE TABLE IF NOT EXISTS rmc_qc_nonconformances (
            id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, ncr_number VARCHAR(60) NOT NULL,
            source_type VARCHAR(80) NOT NULL, source_reference VARCHAR(100) NULL, description TEXT NOT NULL,
            severity ENUM("minor", "major", "critical") NOT NULL DEFAULT "minor", detected_at DATE NOT NULL,
            responsible_party VARCHAR(150) NULL, immediate_action TEXT NULL, root_cause TEXT NULL,
            status ENUM("open", "under_review", "corrective_action", "verified", "closed") NOT NULL DEFAULT "open",
            due_date DATE NULL, evidence_url VARCHAR(255) NULL, created_by INT NULL, closed_at DATETIME NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rmc_qc_ncr (company_id, ncr_number), INDEX idx_rmc_qc_ncr_status (company_id, status)
        )');
        $this->query('CREATE TABLE IF NOT EXISTS rmc_qc_corrective_actions (
            id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, ncr_id INT NOT NULL,
            action_description TEXT NOT NULL, owner_name VARCHAR(150) NOT NULL, due_date DATE NULL,
            status ENUM("open", "in_progress", "completed", "verified") NOT NULL DEFAULT "open",
            completion_notes TEXT NULL, completed_at DATETIME NULL, verified_by INT NULL, verified_at DATETIME NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ncr_id) REFERENCES rmc_qc_nonconformances(id) ON DELETE CASCADE, INDEX idx_rmc_qc_action_status (company_id, status)
        )');
        $this->query('CREATE TABLE IF NOT EXISTS rmc_maintenance_jobs (
            id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, job_card VARCHAR(60) NOT NULL,
            asset_name VARCHAR(150) NOT NULL, issue_description TEXT NOT NULL, scheduled_date DATE NULL,
            maintenance_type ENUM("corrective", "preventive", "inspection", "emergency") NOT NULL DEFAULT "corrective",
            meter_reading DECIMAL(14,2) NULL, meter_unit VARCHAR(30) NULL, priority ENUM("low", "normal", "high", "critical") NOT NULL DEFAULT "normal",
            cost DECIMAL(14,2) NOT NULL DEFAULT 0, downtime_hours DECIMAL(10,2) NOT NULL DEFAULT 0,
            technician_name VARCHAR(150) NULL, root_cause TEXT NULL, completion_notes TEXT NULL,
            status ENUM("open", "scheduled", "in_progress", "completed", "cancelled") NOT NULL DEFAULT "open",
            notes TEXT NULL, created_by INT NULL, completed_at DATETIME NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rmc_maintenance_job (company_id, job_card), INDEX idx_rmc_maintenance_status (company_id, status)
        )');
        foreach (['maintenance_type ENUM("corrective", "preventive", "inspection", "emergency") NOT NULL DEFAULT "corrective"', 'meter_reading DECIMAL(14,2) NULL', 'meter_unit VARCHAR(30) NULL', 'priority ENUM("low", "normal", "high", "critical") NOT NULL DEFAULT "normal"', 'downtime_hours DECIMAL(10,2) NOT NULL DEFAULT 0', 'technician_name VARCHAR(150) NULL', 'root_cause TEXT NULL', 'completion_notes TEXT NULL', 'completed_at DATETIME NULL'] as $column) {
            $columnName = explode(' ', $column, 2)[0];
            $this->query('ALTER TABLE rmc_maintenance_jobs ADD COLUMN IF NOT EXISTS ' . $columnName . ' ' . substr($column, strlen($columnName) + 1));
        }
        $this->query('CREATE TABLE IF NOT EXISTS rmc_maintenance_assets (
            id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, asset_code VARCHAR(60) NOT NULL,
            asset_name VARCHAR(150) NOT NULL, asset_type VARCHAR(100) NOT NULL, make_model VARCHAR(120) NULL,
            serial_number VARCHAR(120) NULL, location VARCHAR(150) NULL, assigned_to VARCHAR(150) NULL,
            purchase_date DATE NULL, meter_reading DECIMAL(14,2) NULL, meter_unit VARCHAR(30) NULL,
            status ENUM("active", "under_maintenance", "inactive", "disposed") NOT NULL DEFAULT "active",
            notes TEXT NULL, created_by INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rmc_asset (company_id, asset_code), INDEX idx_rmc_asset_status (company_id, status)
        )');
        $this->query('CREATE TABLE IF NOT EXISTS rmc_maintenance_schedules (
            id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, asset_id INT NULL, schedule_code VARCHAR(60) NOT NULL,
            task_name VARCHAR(180) NOT NULL, frequency_value INT NOT NULL DEFAULT 1, frequency_unit ENUM("days", "hours", "cycles") NOT NULL DEFAULT "days",
            last_service_date DATE NULL, next_due_date DATE NULL, next_due_meter DECIMAL(14,2) NULL, assigned_to VARCHAR(150) NULL,
            checklist TEXT NULL, status ENUM("active", "paused", "completed") NOT NULL DEFAULT "active", created_by INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rmc_schedule (company_id, schedule_code), INDEX idx_rmc_schedule_due (company_id, next_due_date, status)
        )');
        $this->query('CREATE TABLE IF NOT EXISTS rmc_maintenance_parts (
            id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, job_id INT NULL, asset_id INT NULL,
            part_name VARCHAR(150) NOT NULL, part_number VARCHAR(80) NULL, quantity DECIMAL(12,2) NOT NULL DEFAULT 0,
            unit_cost DECIMAL(14,2) NOT NULL DEFAULT 0, issued_at DATE NOT NULL, issued_by INT NULL, notes TEXT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_rmc_parts_company (company_id, issued_at)
        )');
        $this->query('CREATE TABLE IF NOT EXISTS rmc_maintenance_fuel (
            id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, asset_id INT NULL, job_id INT NULL,
            fuel_type VARCHAR(50) NOT NULL, quantity DECIMAL(12,2) NOT NULL DEFAULT 0, unit_cost DECIMAL(14,2) NOT NULL DEFAULT 0,
            meter_reading DECIMAL(14,2) NULL, issued_at DATE NOT NULL, issued_by INT NULL, notes TEXT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_rmc_fuel_company (company_id, issued_at)
        )');
        $this->query('CREATE TABLE IF NOT EXISTS rmc_mix_designs (
            id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, mix_code VARCHAR(60) NOT NULL,
            concrete_grade VARCHAR(60) NOT NULL, project_reference VARCHAR(120) NULL, design_method VARCHAR(120) NULL,
            target_strength_mpa DECIMAL(6,2) NOT NULL DEFAULT 0, slump_mm DECIMAL(8,2) NOT NULL DEFAULT 0,
            water_cement_ratio DECIMAL(6,3) NOT NULL DEFAULT 0, cement_kg_m3 DECIMAL(12,2) NOT NULL DEFAULT 0,
            fine_aggregate_kg_m3 DECIMAL(12,2) NOT NULL DEFAULT 0, coarse_aggregate_kg_m3 DECIMAL(12,2) NOT NULL DEFAULT 0,
            water_kg_m3 DECIMAL(12,2) NOT NULL DEFAULT 0, admixture_kg_m3 DECIMAL(12,2) NOT NULL DEFAULT 0,
            max_aggregate_size_mm DECIMAL(8,2) NOT NULL DEFAULT 0, air_content_percent DECIMAL(6,2) NOT NULL DEFAULT 0,
            batch_volume_m3 DECIMAL(12,2) NOT NULL DEFAULT 0, trial_number VARCHAR(60) NULL, revision_no INT NOT NULL DEFAULT 1,
            status ENUM("draft", "trial", "pending_approval", "approved", "rejected", "archived") NOT NULL DEFAULT "draft",
            approved_by VARCHAR(150) NULL, approved_at DATETIME NULL, notes TEXT NULL, created_by INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rmc_mix_design (company_id, mix_code), INDEX idx_rmc_mix_status (company_id, status), INDEX idx_rmc_mix_revision (company_id, mix_code, revision_no)
        )');
        foreach (['project_reference VARCHAR(120) NULL', 'design_method VARCHAR(120) NULL', 'target_strength_mpa DECIMAL(6,2) NOT NULL DEFAULT 0', 'slump_mm DECIMAL(8,2) NOT NULL DEFAULT 0', 'cement_kg_m3 DECIMAL(12,2) NOT NULL DEFAULT 0', 'fine_aggregate_kg_m3 DECIMAL(12,2) NOT NULL DEFAULT 0', 'coarse_aggregate_kg_m3 DECIMAL(12,2) NOT NULL DEFAULT 0', 'water_kg_m3 DECIMAL(12,2) NOT NULL DEFAULT 0', 'admixture_kg_m3 DECIMAL(12,2) NOT NULL DEFAULT 0', 'max_aggregate_size_mm DECIMAL(8,2) NOT NULL DEFAULT 0', 'air_content_percent DECIMAL(6,2) NOT NULL DEFAULT 0', 'batch_volume_m3 DECIMAL(12,2) NOT NULL DEFAULT 0', 'trial_number VARCHAR(60) NULL', 'revision_no INT NOT NULL DEFAULT 1', 'approved_by VARCHAR(150) NULL', 'approved_at DATETIME NULL'] as $column) {
            $columnName = explode(' ', $column, 2)[0];
            $this->query('ALTER TABLE rmc_mix_designs ADD COLUMN IF NOT EXISTS ' . $columnName . ' ' . substr($column, strlen($columnName) + 1));
        }
        $this->query('ALTER TABLE rmc_mix_designs MODIFY status ENUM("draft", "trial", "pending_approval", "approved", "rejected", "archived") NOT NULL DEFAULT "draft"');
        $this->query('CREATE TABLE IF NOT EXISTS rmc_dispatches (
            id INT PRIMARY KEY AUTO_INCREMENT, company_id INT NOT NULL, dispatch_number VARCHAR(60) NOT NULL,
            order_reference VARCHAR(60) NULL, vehicle_number VARCHAR(40) NULL, driver_name VARCHAR(120) NULL,
            delivery_location VARCHAR(180) NOT NULL, quantity_m3 DECIMAL(12,2) NOT NULL DEFAULT 0, dispatch_date DATE NOT NULL,
            status ENUM("planned", "loaded", "in_transit", "delivered", "returned", "cancelled") NOT NULL DEFAULT "planned",
            mix_design_id INT NULL, batch_reference VARCHAR(100) NULL, notes TEXT NULL, created_by INT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_rmc_dispatch (company_id, dispatch_number), INDEX idx_rmc_dispatch_status (company_id, status)
        )');
        foreach (['dispatch_type ENUM("mixed_cement", "materials", "staff") NOT NULL DEFAULT "mixed_cement"', 'inventory_item_id INT NULL', 'staff_id INT NULL', 'item_name VARCHAR(160) NULL', 'quantity DECIMAL(12,2) NOT NULL DEFAULT 0', 'unit VARCHAR(40) NULL', 'issued_to VARCHAR(180) NULL'] as $column) {
            $columnName = explode(' ', $column, 2)[0];
            $this->query('ALTER TABLE rmc_dispatches ADD COLUMN IF NOT EXISTS ' . $columnName . ' ' . substr($column, strlen($columnName) + 1));
        }
        foreach (['mix_design_id INT NULL', 'batch_reference VARCHAR(100) NULL'] as $column) {
            $columnName = explode(' ', $column, 2)[0];
            $this->query('ALTER TABLE rmc_dispatches ADD COLUMN IF NOT EXISTS ' . $columnName . ' ' . substr($column, strlen($columnName) + 1));
        }
    }

    public function moduleExists(string $moduleKey): bool
    {
        return isset(self::MODULES[$moduleKey]);
    }

    public function getRecords(string $moduleKey): array
    {
        $module = self::MODULES[$moduleKey] ?? null;
        if ($module === null) {
            return [];
        }

        $companyId = $this->currentCompanyId();

        if ($moduleKey === 'quality_control') {
            return $this->query('SELECT t.*, m.mix_code, m.concrete_grade FROM rmc_quality_tests t LEFT JOIN rmc_mix_designs m ON m.id = t.mix_design_id WHERE t.company_id = ? ORDER BY t.tested_at DESC, t.id DESC LIMIT 100', [$companyId])->fetchAll();
        }

        if ($moduleKey === 'dispatch') {
            return $this->query('SELECT d.*, m.mix_code, m.concrete_grade, i.name AS inventory_item_name, CONCAT(e.first_name, " ", e.last_name) AS staff_name FROM rmc_dispatches d LEFT JOIN rmc_mix_designs m ON m.id = d.mix_design_id LEFT JOIN inventory_items i ON i.id = d.inventory_item_id LEFT JOIN employees e ON e.id = d.staff_id WHERE d.company_id = ? ORDER BY d.dispatch_date DESC, d.id DESC LIMIT 100', [$companyId])->fetchAll();
        }

        return $this->query('SELECT * FROM ' . $module['table'] . ' WHERE company_id = ? ORDER BY created_at DESC, id DESC LIMIT 100', [$companyId])->fetchAll();
    }

    public function getSalesData(): array
    {
        $companyId = $this->currentCompanyId();
        return [
            'leads' => $this->query('SELECT * FROM rmc_sales_leads WHERE company_id = ? ORDER BY created_at DESC, id DESC LIMIT 100', [$companyId])->fetchAll(),
            'campaigns' => $this->query('SELECT * FROM rmc_sales_campaigns WHERE company_id = ? ORDER BY start_date DESC, id DESC LIMIT 50', [$companyId])->fetchAll(),
            'quotes' => $this->query('SELECT q.*, l.lead_number FROM rmc_sales_quotes q LEFT JOIN rmc_sales_leads l ON l.id = q.lead_id WHERE q.company_id = ? ORDER BY q.created_at DESC, q.id DESC LIMIT 100', [$companyId])->fetchAll(),
            'summary' => [
                'open_leads' => (int)$this->query('SELECT COUNT(*) FROM rmc_sales_leads WHERE company_id = ? AND stage NOT IN ("won", "lost")', [$companyId])->fetchColumn(),
                'pipeline_value' => (float)$this->query('SELECT COALESCE(SUM(estimated_value), 0) FROM rmc_sales_leads WHERE company_id = ? AND stage NOT IN ("won", "lost")', [$companyId])->fetchColumn(),
                'active_campaigns' => (int)$this->query('SELECT COUNT(*) FROM rmc_sales_campaigns WHERE company_id = ? AND status = "active"', [$companyId])->fetchColumn(),
                'pending_quotes' => (int)$this->query('SELECT COUNT(*) FROM rmc_sales_quotes WHERE company_id = ? AND status IN ("draft", "pending_approval", "sent")', [$companyId])->fetchColumn(),
            ],
        ];
    }

    public function createLead(array $data, int $userId): void
    {
        $this->query('INSERT INTO rmc_sales_leads (company_id, lead_number, company_name, contact_name, email, phone, source, segment, estimated_value, stage, next_follow_up, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$this->currentCompanyId(), $this->required($data, 'lead_number'), $this->required($data, 'company_name'), $this->required($data, 'contact_name'), trim((string)($data['email'] ?? '')), trim((string)($data['phone'] ?? '')), trim((string)($data['source'] ?? '')), trim((string)($data['segment'] ?? '')), max(0, (float)($data['estimated_value'] ?? 0)), $this->enum($data['stage'] ?? 'new', ['new', 'qualified', 'proposal', 'negotiation', 'won', 'lost']), $this->nullableDate($data['next_follow_up'] ?? null), trim((string)($data['notes'] ?? '')), $userId]);
    }

    public function createCampaign(array $data, int $userId): void
    {
        $this->query('INSERT INTO rmc_sales_campaigns (company_id, campaign_code, campaign_name, channel, segment, budget, start_date, end_date, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$this->currentCompanyId(), $this->required($data, 'campaign_code'), $this->required($data, 'campaign_name'), $this->required($data, 'channel'), trim((string)($data['segment'] ?? '')), max(0, (float)($data['budget'] ?? 0)), $this->requiredDate($data, 'start_date'), $this->nullableDate($data['end_date'] ?? null), $this->enum($data['status'] ?? 'planned', ['planned', 'active', 'paused', 'completed']), trim((string)($data['notes'] ?? '')), $userId]);
    }

    public function createQuote(array $data, int $userId): void
    {
        $this->query('INSERT INTO rmc_sales_quotes (company_id, quote_number, lead_id, customer_name, concrete_grade, quantity_m3, unit_price, discount, valid_until, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$this->currentCompanyId(), $this->required($data, 'quote_number'), !empty($data['lead_id']) ? (int)$data['lead_id'] : null, $this->required($data, 'customer_name'), $this->required($data, 'concrete_grade'), $this->positive($data, 'quantity_m3'), max(0, (float)($data['unit_price'] ?? 0)), max(0, (float)($data['discount'] ?? 0)), $this->nullableDate($data['valid_until'] ?? null), $this->enum($data['status'] ?? 'draft', ['draft', 'pending_approval', 'sent', 'accepted', 'rejected', 'expired']), trim((string)($data['notes'] ?? '')), $userId]);
    }

    public function getQualityData(): array
    {
        $companyId = $this->currentCompanyId();
        return [
            'tests' => $this->query('SELECT t.*, m.mix_code, m.concrete_grade FROM rmc_quality_tests t LEFT JOIN rmc_mix_designs m ON m.id = t.mix_design_id WHERE t.company_id = ? ORDER BY t.tested_at DESC, t.id DESC LIMIT 100', [$companyId])->fetchAll(),
            'plans' => $this->query('SELECT * FROM rmc_qc_plans WHERE company_id = ? ORDER BY created_at DESC, id DESC LIMIT 50', [$companyId])->fetchAll(),
            'inspections' => $this->query('SELECT * FROM rmc_qc_inspections WHERE company_id = ? ORDER BY inspected_at DESC, id DESC LIMIT 100', [$companyId])->fetchAll(),
            'ncrs' => $this->query('SELECT * FROM rmc_qc_nonconformances WHERE company_id = ? ORDER BY detected_at DESC, id DESC LIMIT 100', [$companyId])->fetchAll(),
            'actions' => $this->query('SELECT a.*, n.ncr_number FROM rmc_qc_corrective_actions a INNER JOIN rmc_qc_nonconformances n ON n.id = a.ncr_id WHERE a.company_id = ? ORDER BY a.due_date ASC, a.id DESC LIMIT 100', [$companyId])->fetchAll(),
            'approvedMixes' => $this->query('SELECT id, mix_code, concrete_grade, target_strength_mpa, status FROM rmc_mix_designs WHERE company_id = ? AND status = "approved" ORDER BY created_at DESC, id DESC LIMIT 100', [$companyId])->fetchAll(),
            'summary' => [
                'open_ncrs' => (int)$this->query('SELECT COUNT(*) FROM rmc_qc_nonconformances WHERE company_id = ? AND status NOT IN ("closed", "verified")', [$companyId])->fetchColumn(),
                'overdue_actions' => (int)$this->query('SELECT COUNT(*) FROM rmc_qc_corrective_actions WHERE company_id = ? AND due_date < CURDATE() AND status NOT IN ("completed", "verified")', [$companyId])->fetchColumn(),
                'failed_tests' => (int)$this->query('SELECT COUNT(*) FROM rmc_quality_tests WHERE company_id = ? AND status = "failed"', [$companyId])->fetchColumn(),
                'passed_inspections' => (int)$this->query('SELECT COUNT(*) FROM rmc_qc_inspections WHERE company_id = ? AND status = "passed"', [$companyId])->fetchColumn(),
            ],
        ];
    }

    public function getMaintenanceData(): array
    {
        $companyId = $this->currentCompanyId();
        return [
            'assets' => $this->query('SELECT * FROM rmc_maintenance_assets WHERE company_id = ? ORDER BY asset_name ASC', [$companyId])->fetchAll(),
            'jobs' => $this->query('SELECT * FROM rmc_maintenance_jobs WHERE company_id = ? ORDER BY created_at DESC, id DESC LIMIT 100', [$companyId])->fetchAll(),
            'schedules' => $this->query('SELECT s.*, a.asset_name FROM rmc_maintenance_schedules s LEFT JOIN rmc_maintenance_assets a ON a.id = s.asset_id WHERE s.company_id = ? ORDER BY s.next_due_date ASC, s.id DESC LIMIT 100', [$companyId])->fetchAll(),
            'parts' => $this->query('SELECT * FROM rmc_maintenance_parts WHERE company_id = ? ORDER BY issued_at DESC, id DESC LIMIT 100', [$companyId])->fetchAll(),
            'fuel' => $this->query('SELECT * FROM rmc_maintenance_fuel WHERE company_id = ? ORDER BY issued_at DESC, id DESC LIMIT 100', [$companyId])->fetchAll(),
            'summary' => [
                'active_assets' => (int)$this->query('SELECT COUNT(*) FROM rmc_maintenance_assets WHERE company_id = ? AND status <> "disposed"', [$companyId])->fetchColumn(),
                'open_jobs' => (int)$this->query('SELECT COUNT(*) FROM rmc_maintenance_jobs WHERE company_id = ? AND status NOT IN ("completed", "cancelled")', [$companyId])->fetchColumn(),
                'overdue_services' => (int)$this->query('SELECT COUNT(*) FROM rmc_maintenance_schedules WHERE company_id = ? AND status = "active" AND next_due_date < CURDATE()', [$companyId])->fetchColumn(),
                'downtime_hours' => (float)$this->query('SELECT COALESCE(SUM(downtime_hours), 0) FROM rmc_maintenance_jobs WHERE company_id = ?', [$companyId])->fetchColumn(),
                'maintenance_cost' => (float)$this->query('SELECT COALESCE(SUM(cost), 0) FROM rmc_maintenance_jobs WHERE company_id = ?', [$companyId])->fetchColumn(),
                'fuel_cost' => (float)$this->query('SELECT COALESCE(SUM(quantity * unit_cost), 0) FROM rmc_maintenance_fuel WHERE company_id = ?', [$companyId])->fetchColumn(),
            ],
        ];
    }

    public function getMixDesignData(): array
    {
        $companyId = $this->currentCompanyId();
        return [
            'mixes' => $this->query('SELECT * FROM rmc_mix_designs WHERE company_id = ? ORDER BY created_at DESC, id DESC LIMIT 100', [$companyId])->fetchAll(),
            'approvedMixes' => $this->query('SELECT * FROM rmc_mix_designs WHERE company_id = ? AND status = "approved" ORDER BY approved_at DESC, id DESC LIMIT 20', [$companyId])->fetchAll(),
            'approvalQueue' => $this->query('SELECT * FROM rmc_mix_designs WHERE company_id = ? AND status IN ("draft", "trial", "pending_approval") ORDER BY created_at DESC, id DESC LIMIT 20', [$companyId])->fetchAll(),
            'summary' => [
                'total_designs' => (int)$this->query('SELECT COUNT(*) FROM rmc_mix_designs WHERE company_id = ?', [$companyId])->fetchColumn(),
                'approved_designs' => (int)$this->query('SELECT COUNT(*) FROM rmc_mix_designs WHERE company_id = ? AND status = "approved"', [$companyId])->fetchColumn(),
                'pending_approval' => (int)$this->query('SELECT COUNT(*) FROM rmc_mix_designs WHERE company_id = ? AND status IN ("draft", "trial", "pending_approval")', [$companyId])->fetchColumn(),
                'average_wc_ratio' => (float)$this->query('SELECT COALESCE(AVG(water_cement_ratio), 0) FROM rmc_mix_designs WHERE company_id = ?', [$companyId])->fetchColumn(),
                'avg_target_strength' => (float)$this->query('SELECT COALESCE(AVG(target_strength_mpa), 0) FROM rmc_mix_designs WHERE company_id = ?', [$companyId])->fetchColumn(),
            ],
        ];
    }

    public function createMaintenanceAsset(array $data, int $userId): void
    {
        $this->query('INSERT INTO rmc_maintenance_assets (company_id, asset_code, asset_name, asset_type, make_model, serial_number, location, assigned_to, purchase_date, meter_reading, meter_unit, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$this->currentCompanyId(), $this->required($data, 'asset_code'), $this->required($data, 'asset_name'), $this->required($data, 'asset_type'), trim((string)($data['make_model'] ?? '')), trim((string)($data['serial_number'] ?? '')), trim((string)($data['location'] ?? '')), trim((string)($data['assigned_to'] ?? '')), $this->nullableDate($data['purchase_date'] ?? null), $this->nullableNumber($data['meter_reading'] ?? null), trim((string)($data['meter_unit'] ?? '')), $this->enum($data['status'] ?? 'active', ['active', 'under_maintenance', 'inactive', 'disposed']), trim((string)($data['notes'] ?? '')), $userId]);
    }

    public function createMaintenanceSchedule(array $data, int $userId): void
    {
        $this->query('INSERT INTO rmc_maintenance_schedules (company_id, asset_id, schedule_code, task_name, frequency_value, frequency_unit, last_service_date, next_due_date, next_due_meter, assigned_to, checklist, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$this->currentCompanyId(), !empty($data['asset_id']) ? (int)$data['asset_id'] : null, $this->required($data, 'schedule_code'), $this->required($data, 'task_name'), max(1, (int)($data['frequency_value'] ?? 1)), $this->enum($data['frequency_unit'] ?? 'days', ['days', 'hours', 'cycles']), $this->nullableDate($data['last_service_date'] ?? null), $this->nullableDate($data['next_due_date'] ?? null), $this->nullableNumber($data['next_due_meter'] ?? null), trim((string)($data['assigned_to'] ?? '')), trim((string)($data['checklist'] ?? '')), $this->enum($data['status'] ?? 'active', ['active', 'paused', 'completed']), $userId]);
    }

    public function createMaintenancePart(array $data, int $userId): void
    {
        $this->query('INSERT INTO rmc_maintenance_parts (company_id, job_id, asset_id, part_name, part_number, quantity, unit_cost, issued_at, issued_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$this->currentCompanyId(), !empty($data['job_id']) ? (int)$data['job_id'] : null, !empty($data['asset_id']) ? (int)$data['asset_id'] : null, $this->required($data, 'part_name'), trim((string)($data['part_number'] ?? '')), $this->positive($data, 'quantity'), max(0, (float)($data['unit_cost'] ?? 0)), $this->requiredDate($data, 'issued_at'), $userId, trim((string)($data['notes'] ?? ''))]);
    }

    public function createMaintenanceFuel(array $data, int $userId): void
    {
        $this->query('INSERT INTO rmc_maintenance_fuel (company_id, asset_id, job_id, fuel_type, quantity, unit_cost, meter_reading, issued_at, issued_by, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$this->currentCompanyId(), !empty($data['asset_id']) ? (int)$data['asset_id'] : null, !empty($data['job_id']) ? (int)$data['job_id'] : null, $this->required($data, 'fuel_type'), $this->positive($data, 'quantity'), max(0, (float)($data['unit_cost'] ?? 0)), $this->nullableNumber($data['meter_reading'] ?? null), $this->requiredDate($data, 'issued_at'), $userId, trim((string)($data['notes'] ?? ''))]);
    }

    private function nullableNumber(mixed $value): ?float
    {
        return trim((string)$value) === '' ? null : (float)$value;
    }

    public function createMixDesign(array $data, int $userId): void
    {
        $companyId = $this->currentCompanyId();
        $mixCode = $this->required($data, 'mix_code');
        $status = $this->enum($data['status'] ?? 'draft', ['draft', 'trial', 'pending_approval', 'approved', 'rejected', 'archived']);
        $revisionNo = max(1, (int)($data['revision_no'] ?? 1));

        $this->query('INSERT INTO rmc_mix_designs (
            company_id, mix_code, concrete_grade, project_reference, design_method, target_strength_mpa, slump_mm,
            water_cement_ratio, cement_kg_m3, fine_aggregate_kg_m3, coarse_aggregate_kg_m3, water_kg_m3,
            admixture_kg_m3, max_aggregate_size_mm, air_content_percent, batch_volume_m3, trial_number,
            revision_no, status, approved_by, approved_at, notes, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $companyId,
            $mixCode,
            $this->required($data, 'concrete_grade'),
            trim((string)($data['project_reference'] ?? '')),
            trim((string)($data['design_method'] ?? '')),
            max(0, (float)($data['target_strength_mpa'] ?? 0)),
            max(0, (float)($data['slump_mm'] ?? 0)),
            $this->positive($data, 'water_cement_ratio'),
            max(0, (float)($data['cement_kg_m3'] ?? 0)),
            max(0, (float)($data['fine_aggregate_kg_m3'] ?? 0)),
            max(0, (float)($data['coarse_aggregate_kg_m3'] ?? 0)),
            max(0, (float)($data['water_kg_m3'] ?? 0)),
            max(0, (float)($data['admixture_kg_m3'] ?? 0)),
            max(0, (float)($data['max_aggregate_size_mm'] ?? 0)),
            max(0, (float)($data['air_content_percent'] ?? 0)),
            max(0, (float)($data['batch_volume_m3'] ?? 0)),
            trim((string)($data['trial_number'] ?? '')),
            $revisionNo,
            $status,
            trim((string)($data['approved_by'] ?? '')),
            $status === 'approved' ? date('Y-m-d H:i:s') : null,
            trim((string)($data['notes'] ?? '')),
            $userId,
        ]);
    }

    public function createQualityPlan(array $data, int $userId): void
    {
        $this->query('INSERT INTO rmc_qc_plans (company_id, plan_number, title, project_reference, standard, responsible_person, checklist, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [$this->currentCompanyId(), $this->required($data, 'plan_number'), $this->required($data, 'title'), trim((string)($data['project_reference'] ?? '')), trim((string)($data['standard'] ?? '')), trim((string)($data['responsible_person'] ?? '')), trim((string)($data['checklist'] ?? '')), $this->enum($data['status'] ?? 'draft', ['draft', 'active', 'closed']), $userId]);
    }

    public function createQualityInspection(array $data, int $userId): void
    {
        $this->query('INSERT INTO rmc_qc_inspections (company_id, plan_id, inspection_number, inspection_type, location, inspected_at, inspector_name, status, observations, evidence_url, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$this->currentCompanyId(), !empty($data['plan_id']) ? (int)$data['plan_id'] : null, $this->required($data, 'inspection_number'), $this->required($data, 'inspection_type'), $this->required($data, 'location'), $this->requiredDate($data, 'inspected_at'), $this->required($data, 'inspector_name'), $this->enum($data['status'] ?? 'pending', ['pending', 'passed', 'conditional', 'failed']), trim((string)($data['observations'] ?? '')), trim((string)($data['evidence_url'] ?? '')), $userId]);
    }

    public function createNcr(array $data, int $userId): void
    {
        $this->query('INSERT INTO rmc_qc_nonconformances (company_id, ncr_number, source_type, source_reference, description, severity, detected_at, responsible_party, immediate_action, root_cause, status, due_date, evidence_url, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$this->currentCompanyId(), $this->required($data, 'ncr_number'), $this->required($data, 'source_type'), trim((string)($data['source_reference'] ?? '')), $this->required($data, 'description'), $this->enum($data['severity'] ?? 'minor', ['minor', 'major', 'critical']), $this->requiredDate($data, 'detected_at'), trim((string)($data['responsible_party'] ?? '')), trim((string)($data['immediate_action'] ?? '')), trim((string)($data['root_cause'] ?? '')), 'open', $this->nullableDate($data['due_date'] ?? null), trim((string)($data['evidence_url'] ?? '')), $userId]);
    }

    public function createCorrectiveAction(array $data, int $userId): void
    {
        $ncrId = (int)($data['ncr_id'] ?? 0);
        if (!$this->query('SELECT id FROM rmc_qc_nonconformances WHERE id = ? AND company_id = ? LIMIT 1', [$ncrId, $this->currentCompanyId()])->fetchColumn()) {
            throw new InvalidArgumentException('Select a valid NCR.');
        }
        $this->query('INSERT INTO rmc_qc_corrective_actions (company_id, ncr_id, action_description, owner_name, due_date, status, completion_notes) VALUES (?, ?, ?, ?, ?, ?, ?)', [$this->currentCompanyId(), $ncrId, $this->required($data, 'action_description'), $this->required($data, 'owner_name'), $this->nullableDate($data['due_date'] ?? null), $this->enum($data['status'] ?? 'open', ['open', 'in_progress', 'completed', 'verified']), trim((string)($data['completion_notes'] ?? ''))]);
    }

    public function create(string $moduleKey, array $data, int $userId): void
    {
        $companyId = $this->currentCompanyId();
        switch ($moduleKey) {
            case 'sales_marketing':
                $this->query('INSERT INTO rmc_sales_orders (company_id, order_number, customer_name, concrete_grade, quantity_m3, delivery_date, amount, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$companyId, $this->required($data, 'order_number'), $this->required($data, 'customer_name'), $this->required($data, 'concrete_grade'), $this->positive($data, 'quantity_m3'), $this->nullableDate($data['delivery_date'] ?? null), max(0, (float)($data['amount'] ?? 0)), $this->enum($data['status'] ?? 'draft', ['draft', 'quoted', 'approved', 'dispatched', 'completed', 'cancelled']), trim((string)($data['notes'] ?? '')), $userId]);
                return;
            case 'quality_control':
                $mixDesignId = !empty($data['mix_design_id']) ? (int)$data['mix_design_id'] : null;
                $this->query('INSERT INTO rmc_quality_tests (company_id, test_number, batch_reference, test_type, result, tested_at, status, standard, acceptance_criteria, measured_value, unit, sample_location, inspector_name, evidence_url, notes, mix_design_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$companyId, $this->required($data, 'test_number'), $this->required($data, 'batch_reference'), $this->required($data, 'test_type'), $this->required($data, 'result'), $this->requiredDate($data, 'tested_at'), $this->enum($data['status'] ?? 'pending', ['pending', 'passed', 'failed', 'retest']), trim((string)($data['standard'] ?? '')), trim((string)($data['acceptance_criteria'] ?? '')), trim((string)($data['measured_value'] ?? '')), trim((string)($data['unit'] ?? '')), trim((string)($data['sample_location'] ?? '')), trim((string)($data['inspector_name'] ?? '')), trim((string)($data['evidence_url'] ?? '')), trim((string)($data['notes'] ?? '')), $mixDesignId, $userId]);
                return;
            case 'workshop_maintenance':
                $this->query('INSERT INTO rmc_maintenance_jobs (company_id, job_card, asset_name, issue_description, scheduled_date, maintenance_type, meter_reading, meter_unit, priority, cost, downtime_hours, technician_name, root_cause, completion_notes, status, notes, created_by, completed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$companyId, $this->required($data, 'job_card'), $this->required($data, 'asset_name'), $this->required($data, 'issue_description'), $this->nullableDate($data['scheduled_date'] ?? null), $this->enum($data['maintenance_type'] ?? 'corrective', ['corrective', 'preventive', 'inspection', 'emergency']), $this->nullableNumber($data['meter_reading'] ?? null), trim((string)($data['meter_unit'] ?? '')), $this->enum($data['priority'] ?? 'normal', ['low', 'normal', 'high', 'critical']), max(0, (float)($data['cost'] ?? 0)), max(0, (float)($data['downtime_hours'] ?? 0)), trim((string)($data['technician_name'] ?? '')), trim((string)($data['root_cause'] ?? '')), trim((string)($data['completion_notes'] ?? '')), $this->enum($data['status'] ?? 'open', ['open', 'scheduled', 'in_progress', 'completed', 'cancelled']), trim((string)($data['notes'] ?? '')), $userId, ($data['status'] ?? '') === 'completed' ? date('Y-m-d H:i:s') : null]);
                return;
            case 'mix_design':
                $this->createMixDesign($data, $userId);
                return;
            case 'dispatch':
                $dispatchType = $this->enum($data['dispatch_type'] ?? 'mixed_cement', ['mixed_cement', 'materials', 'staff']);
                $dispatchNumber = $this->required($data, 'dispatch_number');
                $deliveryLocation = $this->required($data, 'delivery_location');
                $dispatchDate = $this->requiredDate($data, 'dispatch_date');
                $status = $this->enum($data['status'] ?? 'planned', ['planned', 'loaded', 'in_transit', 'delivered', 'returned', 'cancelled']);
                $mixDesignId = !empty($data['mix_design_id']) ? (int)$data['mix_design_id'] : null;
                $inventoryItemId = !empty($data['inventory_item_id']) ? (int)$data['inventory_item_id'] : null;
                $staffId = !empty($data['staff_id']) ? (int)$data['staff_id'] : null;
                $itemName = trim((string)($data['item_name'] ?? ''));
                $unit = trim((string)($data['unit'] ?? ''));
                $issuedTo = trim((string)($data['issued_to'] ?? ''));
                $quantityValue = isset($data['quantity_m3']) ? (float)($data['quantity_m3'] ?? 0) : (float)($data['quantity'] ?? 0);
                $quantityM3 = $dispatchType === 'mixed_cement' ? $this->positive($data, 'quantity_m3') : max(0, (float)($data['quantity_m3'] ?? 0));
                $quantity = $dispatchType === 'mixed_cement' ? max(0, (float)($data['quantity'] ?? 0)) : $this->positive($data, 'quantity');

                if ($dispatchType === 'materials') {
                    if ($inventoryItemId === null && $itemName === '') {
                        throw new InvalidArgumentException('Select an inventory item or enter an item name for material dispatch.');
                    }
                    if ($unit === '') {
                        throw new InvalidArgumentException('Enter the material unit.');
                    }
                }

                if ($dispatchType === 'staff') {
                    if ($staffId === null) {
                        throw new InvalidArgumentException('Select a staff member for staff dispatch.');
                    }
                    if ($unit === '') {
                        throw new InvalidArgumentException('Enter the staff quantity unit.');
                    }
                }

                $this->query('INSERT INTO rmc_dispatches (company_id, dispatch_number, dispatch_type, order_reference, vehicle_number, driver_name, delivery_location, quantity_m3, quantity, inventory_item_id, staff_id, item_name, unit, issued_to, dispatch_date, status, mix_design_id, batch_reference, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$companyId, $dispatchNumber, $dispatchType, trim((string)($data['order_reference'] ?? '')), trim((string)($data['vehicle_number'] ?? '')), trim((string)($data['driver_name'] ?? '')), $deliveryLocation, $quantityM3, $quantity, $inventoryItemId, $staffId, $itemName !== '' ? $itemName : null, $unit !== '' ? $unit : null, $issuedTo !== '' ? $issuedTo : null, $dispatchDate, $status, $mixDesignId, trim((string)($data['batch_reference'] ?? '')), trim((string)($data['notes'] ?? '')), $userId]);
                return;
        }
        throw new InvalidArgumentException('Unknown RMC module.');
    }

    public function report(): array
    {
        $companyId = $this->currentCompanyId();

        $dispatchCount = (int)$this->query('SELECT COUNT(*) FROM rmc_dispatches WHERE company_id = ?', [$companyId])->fetchColumn();
        $deliveredCount = (int)$this->query('SELECT COUNT(*) FROM rmc_dispatches WHERE company_id = ? AND status = "delivered"', [$companyId])->fetchColumn();
        $activeDispatchCount = (int)$this->query('SELECT COUNT(*) FROM rmc_dispatches WHERE company_id = ? AND status IN ("planned", "loaded", "in_transit", "delivered")', [$companyId])->fetchColumn();
        $qualityPassRate = (float)$this->query('SELECT COALESCE(100 * SUM(status = "passed") / NULLIF(SUM(status IN ("passed", "failed")), 0), 0) FROM rmc_quality_tests WHERE company_id = ?', [$companyId])->fetchColumn();
        $dataCoverage = (float)$this->query('SELECT COALESCE(100 * (
                (SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM rmc_sales_orders WHERE company_id = ?) +
                (SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM rmc_dispatches WHERE company_id = ?) +
                (SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM rmc_quality_tests WHERE company_id = ?) +
                (SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM rmc_maintenance_jobs WHERE company_id = ?) +
                (SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END FROM rmc_mix_designs WHERE company_id = ?)
            ) / 5, 0) AS readiness', [$companyId, $companyId, $companyId, $companyId, $companyId])->fetchColumn();

        $dispatchEfficiency = $dispatchCount > 0 ? (float)(100 * $deliveredCount / $dispatchCount) : 0.0;
        $onTimeDispatchRate = $activeDispatchCount > 0 ? (float)(100 * $deliveredCount / $activeDispatchCount) : 0.0;

        return [
            'orders' => (int)$this->query('SELECT COUNT(*) FROM rmc_sales_orders WHERE company_id = ?', [$companyId])->fetchColumn(),
            'order_value' => (float)$this->query('SELECT COALESCE(SUM(amount), 0) FROM rmc_sales_orders WHERE company_id = ? AND status <> "cancelled"', [$companyId])->fetchColumn(),
            'dispatch_count' => $dispatchCount,
            'dispatched_m3' => (float)$this->query('SELECT COALESCE(SUM(quantity_m3), 0) FROM rmc_dispatches WHERE company_id = ? AND status <> "cancelled"', [$companyId])->fetchColumn(),
            'quality_tests' => (int)$this->query('SELECT COUNT(*) FROM rmc_quality_tests WHERE company_id = ?', [$companyId])->fetchColumn(),
            'quality_pass_rate' => $qualityPassRate,
            'failed_tests' => (int)$this->query('SELECT COUNT(*) FROM rmc_quality_tests WHERE company_id = ? AND status = "failed"', [$companyId])->fetchColumn(),
            'open_ncrs' => (int)$this->query('SELECT COUNT(*) FROM rmc_qc_nonconformances WHERE company_id = ? AND status NOT IN ("closed", "verified")', [$companyId])->fetchColumn(),
            'open_maintenance' => (int)$this->query('SELECT COUNT(*) FROM rmc_maintenance_jobs WHERE company_id = ? AND status NOT IN ("completed", "cancelled")', [$companyId])->fetchColumn(),
            'approved_mix_designs' => (int)$this->query('SELECT COUNT(*) FROM rmc_mix_designs WHERE company_id = ? AND status = "approved"', [$companyId])->fetchColumn(),
            'total_mix_designs' => (int)$this->query('SELECT COUNT(*) FROM rmc_mix_designs WHERE company_id = ?', [$companyId])->fetchColumn(),
            'avg_target_strength' => (float)$this->query('SELECT COALESCE(AVG(target_strength_mpa), 0) FROM rmc_mix_designs WHERE company_id = ?', [$companyId])->fetchColumn(),
            'dispatch_efficiency' => $dispatchEfficiency,
            'on_time_dispatch_rate' => $onTimeDispatchRate,
            'erp_bi_readiness' => $dataCoverage,
            'target_quality_pass_rate' => 92.0,
            'target_dispatch_efficiency' => 90.0,
            'target_on_time_dispatch_rate' => 88.0,
            'sales_velocity' => $this->query('SELECT COALESCE(AVG(amount), 0) FROM rmc_sales_orders WHERE company_id = ? AND status IN ("approved", "dispatched", "completed")', [$companyId])->fetchColumn(),
            'maintenance_risk' => $this->query('SELECT COUNT(*) FROM rmc_maintenance_jobs WHERE company_id = ? AND status NOT IN ("completed", "cancelled")', [$companyId])->fetchColumn(),
        ];
    }

    public function getTrendSeries(string $period = '6m', string $category = 'all'): array
    {
        $monthsToShow = $period === '12m' ? 12 : 6;
        $companyId = $this->currentCompanyId();
        $points = [];
        $today = new DateTimeImmutable('first day of this month');

        for ($offset = $monthsToShow - 1; $offset >= 0; $offset--) {
            $month = $today->modify('-' . $offset . ' months');
            $label = $month->format('M');
            $monthKey = $month->format('Y-m');
            $sales = (int)$this->query('SELECT COUNT(*) FROM rmc_sales_orders WHERE company_id = ? AND DATE_FORMAT(created_at, "%Y-%m") = ?', [$companyId, $monthKey])->fetchColumn();
            $dispatch = (int)$this->query('SELECT COUNT(*) FROM rmc_dispatches WHERE company_id = ? AND DATE_FORMAT(created_at, "%Y-%m") = ?', [$companyId, $monthKey])->fetchColumn();
            $quality = (int)$this->query('SELECT COUNT(*) FROM rmc_quality_tests WHERE company_id = ? AND DATE_FORMAT(created_at, "%Y-%m") = ?', [$companyId, $monthKey])->fetchColumn();
            $maintenance = (int)$this->query('SELECT COUNT(*) FROM rmc_maintenance_jobs WHERE company_id = ? AND DATE_FORMAT(created_at, "%Y-%m") = ?', [$companyId, $monthKey])->fetchColumn();
            $mix = (int)$this->query('SELECT COUNT(*) FROM rmc_mix_designs WHERE company_id = ? AND DATE_FORMAT(created_at, "%Y-%m") = ?', [$companyId, $monthKey])->fetchColumn();

            $value = match ($category) {
                'sales' => $sales,
                'dispatch' => $dispatch,
                'quality' => $quality,
                'maintenance' => $maintenance,
                'mix_design' => $mix,
                default => $sales + $dispatch + $quality + $maintenance + $mix,
            };

            $points[] = ['label' => $label, 'value' => (float)$value, 'month' => $monthKey];
        }

        return $points;
    }

    private function required(array $data, string $key): string
    {
        $value = trim((string)($data[$key] ?? ''));
        if ($value === '') {
            throw new InvalidArgumentException(ucwords(str_replace('_', ' ', $key)) . ' is required.');
        }
        return $value;
    }

    private function requiredDate(array $data, string $key): string
    {
        $value = $this->required($data, $key);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw new InvalidArgumentException('Enter a valid date.');
        }
        return $value;
    }

    private function nullableDate(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $this->requiredDate(['date' => $value], 'date');
    }

    private function positive(array $data, string $key): float
    {
        $value = (float)($data[$key] ?? 0);
        if ($value <= 0) {
            throw new InvalidArgumentException(ucwords(str_replace('_', ' ', $key)) . ' must be greater than zero.');
        }
        return $value;
    }

    private function enum(string $value, array $allowed): string
    {
        return in_array($value, $allowed, true) ? $value : $allowed[0];
    }
}
