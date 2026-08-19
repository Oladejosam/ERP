USE ERP_db;

INSERT INTO roles (name, description) VALUES
('Super Administrator', 'System administrator');

INSERT INTO users (name, email, password_hash, role_id, employee_id, status, created_at)
VALUES (
    'Sammy',
    'test@email.com',
    '$2y$10$LcOPp6CZLs6TZwDNT0Jwte9YnGS3mggFR81PBaWftWnoGqXsrt5Pi',
    1,
    NULL,
    'active',
    NOW()
);
