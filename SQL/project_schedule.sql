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

ALTER TABLE project_daily_progress ADD COLUMN IF NOT EXISTS schedule_id INT NULL;

CREATE TABLE IF NOT EXISTS project_delay_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  company_id INT NOT NULL,
  project_id INT NOT NULL,
  schedule_id INT NOT NULL,
  delay_date DATE NOT NULL,
  delay_days INT NOT NULL,
  reason VARCHAR(180) NOT NULL,
  details TEXT NULL,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (schedule_id) REFERENCES project_schedule(id) ON DELETE CASCADE
);

ALTER TABLE project_delay_logs ADD COLUMN IF NOT EXISTS company_id INT NULL AFTER id;
ALTER TABLE project_delay_logs ADD COLUMN IF NOT EXISTS schedule_id INT NULL AFTER project_id;
ALTER TABLE project_delay_logs ADD COLUMN IF NOT EXISTS delay_date DATE NULL AFTER schedule_id;
ALTER TABLE project_delay_logs ADD COLUMN IF NOT EXISTS delay_days INT NOT NULL DEFAULT 0 AFTER delay_date;
ALTER TABLE project_delay_logs ADD COLUMN IF NOT EXISTS reason VARCHAR(180) NULL AFTER delay_days;
ALTER TABLE project_delay_logs ADD COLUMN IF NOT EXISTS details TEXT NULL AFTER reason;
ALTER TABLE project_delay_logs ADD COLUMN IF NOT EXISTS log_date DATE NULL AFTER project_id;
ALTER TABLE project_delay_logs ADD COLUMN IF NOT EXISTS title VARCHAR(150) NULL AFTER log_date;
ALTER TABLE project_delay_logs ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER title;
ALTER TABLE project_delay_logs ADD COLUMN IF NOT EXISTS impact_days INT NULL AFTER description;
