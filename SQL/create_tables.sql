USE ERP_db;

CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    employee_id INT NULL,
    status ENUM('active','inactive','locked') DEFAULT 'active',
    remember_token VARCHAR(255) NULL,
    last_login DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
);

CREATE TABLE employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_code VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) NOT NULL,
    department VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    designation VARCHAR(150) NULL,
    hire_date DATE NOT NULL,
    salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('active','inactive','terminated') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_code VARCHAR(50) NOT NULL UNIQUE,
    company_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    address TEXT,
    balance DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_code VARCHAR(50) NOT NULL UNIQUE,
    company_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    address TEXT,
    balance DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE inventory_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE inventory_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category_id INT NOT NULL,
    unit VARCHAR(50) NOT NULL,
    cost_price DECIMAL(12,2) DEFAULT 0.00,
    selling_price DECIMAL(12,2) DEFAULT 0.00,
    opening_stock INT DEFAULT 0,
    current_stock INT DEFAULT 0,
    reorder_level INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES inventory_categories(id)
);

CREATE TABLE IF NOT EXISTS projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_number VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    client_id INT NOT NULL,
    client_name VARCHAR(150) NULL,
    consultant VARCHAR(150) NULL,
    contract_value DECIMAL(12,2) DEFAULT 0.00,
    budget DECIMAL(12,2) DEFAULT 0.00,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    site_location VARCHAR(255) NOT NULL,
    progress_percent INT DEFAULT 0,
    status ENUM('planned','in_progress','completed','on_hold','cancelled') DEFAULT 'planned',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES customers(id)
);

CREATE TABLE IF NOT EXISTS project_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    schedule_note TEXT,
    delay_note TEXT,
    notes TEXT,
    progress_percent INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS project_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    progress_percent INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE project_staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    employee_id INT NOT NULL,
    role VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS project_staff_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    employee_id INT NOT NULL,
    action VARCHAR(20) NOT NULL,
    role VARCHAR(100) NOT NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE purchase_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    po_number VARCHAR(50) NOT NULL UNIQUE,
    supplier_id INT NOT NULL,
    project_id INT NULL,
    order_date DATE NOT NULL,
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('draft','approved','received','cancelled') DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

CREATE TABLE goods_received_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grn_number VARCHAR(50) NOT NULL UNIQUE,
    po_id INT NOT NULL,
    received_date DATE NOT NULL,
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('received','partial','pending') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id)
);

CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    project_id INT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    paid_amount DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('draft','sent','paid','overdue','cancelled') DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_number VARCHAR(50) NOT NULL UNIQUE,
    invoice_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    method VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id)
);

CREATE TABLE accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    account_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    type VARCHAR(50) NOT NULL,
    balance DECIMAL(12,2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_number VARCHAR(50) NOT NULL UNIQUE,
    account_id INT NOT NULL,
    entry_date DATE NOT NULL,
    description TEXT NOT NULL,
    debit DECIMAL(12,2) DEFAULT 0.00,
    credit DECIMAL(12,2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id)
);

CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    check_in TIME NULL,
    check_out TIME NULL,
    status VARCHAR(50) DEFAULT 'present',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

CREATE TABLE payrolls (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    payroll_month VARCHAR(20) NOT NULL,
    basic_salary DECIMAL(12,2) NOT NULL,
    allowances DECIMAL(12,2) DEFAULT 0.00,
    deductions DECIMAL(12,2) DEFAULT 0.00,
    net_pay DECIMAL(12,2) NOT NULL,
    sent_to_portal TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

CREATE TABLE leaves (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    leave_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

CREATE TABLE recruitment_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    applicant_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    position VARCHAR(100) NOT NULL,
    status ENUM('applied','interview','hired','rejected') DEFAULT 'applied',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE equipment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    type VARCHAR(100) NOT NULL,
    status VARCHAR(50) DEFAULT 'available',
    project_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

CREATE TABLE assets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asset_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    purchase_date DATE NOT NULL,
    value DECIMAL(12,2) NOT NULL,
    depreciation_rate DECIMAL(5,2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_code VARCHAR(50) NOT NULL UNIQUE,
    plate_number VARCHAR(50) NOT NULL,
    model VARCHAR(150) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE fuel_consumptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    fuel_date DATE NOT NULL,
    liters DECIMAL(10,2) NOT NULL,
    cost DECIMAL(12,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

CREATE TABLE maintenance_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    asset_id INT NOT NULL,
    maintenance_date DATE NOT NULL,
    description TEXT NOT NULL,
    cost DECIMAL(12,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id)
);

CREATE TABLE quality_assurance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    inspection_date DATE NOT NULL,
    result VARCHAR(50) NOT NULL,
    remarks TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

CREATE TABLE safety_incidents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    incident_date DATE NOT NULL,
    severity VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

CREATE TABLE daily_site_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    report_date DATE NOT NULL,
    summary TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

CREATE TABLE tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    assigned_to INT NULL,
    due_date DATE NOT NULL,
    status ENUM('pending','in_progress','completed') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (assigned_to) REFERENCES employees(id)
);

CREATE TABLE milestones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('pending','completed') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    action VARCHAR(150) NOT NULL,
    details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    log_type VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    related_type VARCHAR(100) NULL,
    related_id INT NULL,
    uploaded_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    key_value TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
