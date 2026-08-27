-- Contract Admin module schema for existing ERP databases.
CREATE TABLE IF NOT EXISTS contract_admin_contracts (
  id INT PRIMARY KEY AUTO_INCREMENT,
  company_id INT NOT NULL,
  contract_number VARCHAR(80) NOT NULL,
  title VARCHAR(180) NOT NULL,
  client_name VARCHAR(180) NOT NULL,
  contractor_name VARCHAR(180) DEFAULT NULL,
  procurement_route VARCHAR(80) NOT NULL DEFAULT 'Open tender',
  status ENUM('opportunity','tendering','under_evaluation','awarded','active','completed','unsuccessful','cancelled') NOT NULL DEFAULT 'opportunity',
  estimated_value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  awarded_value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  bid_deadline DATE DEFAULT NULL,
  award_date DATE DEFAULT NULL,
  commencement_date DATE DEFAULT NULL,
  completion_date DATE DEFAULT NULL,
  description TEXT DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_contract_company_number (company_id, contract_number)
);

CREATE TABLE IF NOT EXISTS contract_admin_events (
  id INT PRIMARY KEY AUTO_INCREMENT,
  company_id INT NOT NULL,
  contract_id INT NOT NULL,
  event_type VARCHAR(80) NOT NULL,
  event_date DATE NOT NULL,
  notes TEXT NOT NULL,
  created_by INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (contract_id) REFERENCES contract_admin_contracts(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS contract_admin_documents (
  id INT PRIMARY KEY AUTO_INCREMENT,
  company_id INT NOT NULL,
  contract_id INT NOT NULL,
  document_date DATE NOT NULL,
  label VARCHAR(150) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  file_type VARCHAR(100) NOT NULL,
  file_size INT NOT NULL DEFAULT 0,
  created_by INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (contract_id) REFERENCES contract_admin_contracts(id) ON DELETE CASCADE
);
