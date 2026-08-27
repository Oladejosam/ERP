-- Project schedule table for existing ERP databases.
CREATE TABLE IF NOT EXISTS project_schedule (
  id INT PRIMARY KEY AUTO_INCREMENT,
  company_id INT NOT NULL,
  project_id INT NOT NULL,
  task_name VARCHAR(180) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  status ENUM('planned','in_progress','completed','on_hold') NOT NULL DEFAULT 'planned',
  progress_percent INT NOT NULL DEFAULT 0,
  assigned_to VARCHAR(150) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);
