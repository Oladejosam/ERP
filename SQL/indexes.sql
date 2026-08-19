USE ERP_db;

ALTER TABLE users ADD INDEX idx_users_email (email);
ALTER TABLE users ADD INDEX idx_users_role (role_id);
ALTER TABLE employees ADD INDEX idx_employees_department (department);
ALTER TABLE customers ADD INDEX idx_customers_status (status);
ALTER TABLE suppliers ADD INDEX idx_suppliers_status (status);
ALTER TABLE inventory_items ADD INDEX idx_inventory_category (category_id);
ALTER TABLE projects ADD INDEX idx_projects_status (status);
ALTER TABLE invoices ADD INDEX idx_invoices_status (status);
ALTER TABLE payments ADD INDEX idx_payments_date (payment_date);
ALTER TABLE attendance ADD INDEX idx_attendance_date (attendance_date);
ALTER TABLE payrolls ADD INDEX idx_payrolls_month (payroll_month);
ALTER TABLE audit_logs ADD INDEX idx_audit_logs_created (created_at);
ALTER TABLE notifications ADD INDEX idx_notifications_unread (is_read);
