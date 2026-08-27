-- Link requisitions to a real project site for site quantity surveyor submissions.
ALTER TABLE requisitions ADD COLUMN IF NOT EXISTS project_id INT NULL AFTER company_id;
