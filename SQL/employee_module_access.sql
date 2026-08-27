-- Optional explicit staff module permissions for the current company.
ALTER TABLE users MODIFY COLUMN role_id INT NULL;

CREATE TABLE IF NOT EXISTS employee_module_access (
  company_id INT NOT NULL,
  employee_id INT NOT NULL,
  module_key VARCHAR(50) NOT NULL,
  PRIMARY KEY (company_id, employee_id, module_key),
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);
