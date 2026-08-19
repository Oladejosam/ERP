USE ERP_db;

INSERT INTO roles (name, description) VALUES
('Super Administrator', 'System administrator'),
('Managing Director', 'Executive leadership'),
('Finance Manager', 'Finance oversight'),
('HR Manager', 'Human resources'),
('Site Engineer', 'Engineering oversight'),
('Store Officer', 'Inventory control'),
('Procurement Officer', 'Purchasing'),
('Accountant', 'Financial accounting'),
('Cashier', 'Cash handling'),
('Receptionist', 'Front office'),
('Inventory Officer', 'Stock management'),
('Employee', 'Standard staff'),
('Auditor', 'Compliance and review');

INSERT INTO employees (employee_code, first_name, last_name, email, phone, department, position, hire_date, salary, status) VALUES
('EMP001','Adebayo','Olusegun','adebayo@example.com','08010000001','Operations','Managing Director','2020-01-15',500000.00,'active'),
('EMP002','Chidinma','Okafor','chidinma@example.com','08010000002','Finance','Finance Manager','2020-02-10',350000.00,'active');

-- Create remaining employee records programmatically via SQL loop is not available in plain SQL; this file contains a representative seed set.
INSERT INTO employees (employee_code, first_name, last_name, email, phone, department, position, hire_date, salary, status)
VALUES
('EMP003','Tunde','Akin','tunde@example.com','08010000003','HR','HR Manager','2021-03-12',280000.00,'active'),
('EMP004','Ngozi','Ibrahim','ngozi@example.com','08010000004','Projects','Project Manager','2021-04-05',320000.00,'active'),
('EMP005','Emeka','Nwosu','emeka@example.com','08010000005','Engineering','Site Engineer','2021-05-18',260000.00,'active'),
('EMP006','Aisha','Bello','aisha@example.com','08010000006','Operations','Store Officer','2021-06-20',200000.00,'active');

INSERT INTO customers (customer_code, company_name, contact_person, email, phone, address, balance, status) VALUES
('CUST001','Apex Builders Ltd','Bola Ade','apex@example.com','08020000001','Lagos','50000.00','active'),
('CUST002','Breeze Homes','Kemi Yusuf','breeze@example.com','08020000002','Abuja','120000.00','active');

INSERT INTO suppliers (supplier_code, company_name, contact_person, email, phone, address, balance, status) VALUES
('SUP001','Steelline Industries','Dayo Musa','steelline@example.com','08030000001','Port Harcourt','80000.00','active'),
('SUP002','Cement Plus Co','Rita Danjuma','cementplus@example.com','08030000002','Kano','60000.00','active');

INSERT INTO inventory_categories (name) VALUES
('Steel'),
('Cement'),
('Electrical'),
('Plumbing');

INSERT INTO inventory_items (item_code, name, category_id, unit, cost_price, selling_price, opening_stock, current_stock, reorder_level) VALUES
('INV001','Rebar 12mm',1,'tons',180000.00,210000.00,50,48,10),
('INV002','Portland Cement',2,'bags',4500.00,5500.00,300,290,40),
('INV003','PVC Pipe 50mm',4,'meters',950.00,1250.00,200,195,20),
('INV004','Wiring Cable 2.5mm',3,'meters',180.00,250.00,500,490,50);

INSERT INTO projects (project_number, name, client_id, consultant, contract_value, budget, start_date, end_date, site_location, progress_percent, status) VALUES
('PRJ001','Lagos Tower Project',1,'Arc. Hale',35000000.00,36000000.00,'2024-01-10','2026-12-31','Lagos Island',65,'in_progress'),
('PRJ002','Abuja Housing Estate',2,'Engr. Ojo',28000000.00,30000000.00,'2024-04-05','2026-10-30','Abuja',45,'in_progress');

INSERT INTO purchase_orders (po_number, supplier_id, project_id, order_date, total_amount, status) VALUES
('PO001',1,1,'2025-06-01',1800000.00,'approved'),
('PO002',2,2,'2025-06-02',950000.00,'received');

INSERT INTO goods_received_notes (grn_number, po_id, received_date, total_amount, status) VALUES
('GRN001',1,'2025-06-03',1800000.00,'received'),
('GRN002',2,'2025-06-04',950000.00,'received');

INSERT INTO invoices (invoice_number, customer_id, project_id, invoice_date, due_date, total_amount, paid_amount, status) VALUES
('INV-001',1,1,'2025-06-10','2025-06-25',4500000.00,2500000.00,'paid'),
('INV-002',2,2,'2025-06-12','2025-06-27',3200000.00,1000000.00,'overdue');

INSERT INTO payments (payment_number, invoice_id, payment_date, amount, method) VALUES
('PAY-001',1,'2025-06-15',2500000.00,'bank_transfer'),
('PAY-002',2,'2025-06-16',1000000.00,'cash');

INSERT INTO accounts (account_code, name, type, balance) VALUES
('ACC001','Cash Account','cash',2500000.00),
('ACC002','Bank Account','bank',7500000.00);

INSERT INTO transactions (transaction_number, account_id, entry_date, description, debit, credit) VALUES
('TXN001',1,'2025-06-01','Cash received',0.00,2500000.00),
('TXN002',2,'2025-06-02','Bank deposit',0.00,7500000.00);

INSERT INTO attendance (employee_id, attendance_date, check_in, check_out, status) VALUES
(1,'2025-06-01','08:00:00','17:00:00','present'),
(2,'2025-06-01','08:10:00','17:10:00','present');

INSERT INTO payrolls (employee_id, payroll_month, basic_salary, allowances, deductions, net_pay) VALUES
(1,'2025-06',500000.00,50000.00,12000.00,538000.00),
(2,'2025-06',350000.00,30000.00,10000.00,370000.00);

INSERT INTO leaves (employee_id, leave_type, start_date, end_date, status) VALUES
(1,'annual','2025-07-01','2025-07-03','approved'),
(2,'sick','2025-07-08','2025-07-09','pending');

INSERT INTO recruitment_applications (applicant_name, email, phone, position, status) VALUES
('Samuel Musa','samuel@example.com','08040000001','Site Engineer','interview'),
('Linda Adebisi','linda@example.com','08040000002','Accountant','applied');

INSERT INTO equipment (equipment_code, name, type, status, project_id) VALUES
('EQ001','Excavator','heavy','active',1),
('EQ002','Generator','power','active',2);

INSERT INTO assets (asset_code, name, category, purchase_date, value, depreciation_rate) VALUES
('AS001','Office Laptop','IT','2024-02-01',650000.00,10.00),
('AS002','Forklift','Machinery','2023-10-10',1800000.00,15.00);

INSERT INTO vehicles (vehicle_code, plate_number, model, status) VALUES
('VEH001','ABC123','Toyota Hilux','active'),
('VEH002','XYZ987','Ford Transit','active');

INSERT INTO fuel_consumptions (vehicle_id, fuel_date, liters, cost) VALUES
(1,'2025-06-01',40.00,32000.00),
(2,'2025-06-02',30.00,24000.00);

INSERT INTO maintenance_records (asset_id, maintenance_date, description, cost) VALUES
(1,'2025-06-05','Battery replacement',25000.00),
(2,'2025-06-07','Engine service',90000.00);

INSERT INTO quality_assurance (project_id, inspection_date, result, remarks) VALUES
(1,'2025-06-10','pass','Structural checks passed'),
(2,'2025-06-12','pass','Foundation quality acceptable');

INSERT INTO safety_incidents (project_id, incident_date, severity, description) VALUES
(1,'2025-06-11','minor','Minor hand injury reported'),
(2,'2025-06-14','major','Equipment fall near site');

INSERT INTO daily_site_reports (project_id, report_date, summary) VALUES
(1,'2025-06-15','Foundation work completed successfully'),
(2,'2025-06-16','Material delivery received on schedule');

INSERT INTO tasks (project_id, title, assigned_to, due_date, status) VALUES
(1,'Excavate foundation',1,'2025-06-20','completed'),
(2,'Install plumbing',2,'2025-06-22','in_progress');

INSERT INTO milestones (project_id, name, due_date, status) VALUES
(1,'Foundation Complete','2025-06-20','completed'),
(2,'Plumbing Installation','2025-06-22','pending');

INSERT INTO audit_logs (user_id, action, details) VALUES
(1,'login','User logged in successfully'),
(2,'create_project','Project created');

INSERT INTO notifications (user_id, title, message, is_read) VALUES
(1,'Welcome','Welcome to the ERP system',0),
(2,'Invoice Due','Invoice INV-002 is overdue',0);

INSERT INTO system_logs (log_type, message) VALUES
('info','System initialized'),
('warning','Low stock alert triggered');

INSERT INTO documents (title, file_name, category, related_type, related_id, uploaded_by) VALUES
('Project Contract','contract.pdf','project','project',1,1),
('Invoice Copy','invoice.pdf','finance','invoice',1,2);

INSERT INTO settings (key_name, key_value) VALUES
('company_name','Apex Construction Group'),
('currency','NGN');
