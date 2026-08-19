<?php
declare(strict_types=1);

require_once APP_ROOT . '/core/Model.php';

class CompanyModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensureCompanySettingsTable();
    }

    private function ensureCompanySettingsTable(): void
    {
        $this->query(
            'CREATE TABLE IF NOT EXISTS company_settings (
                id TINYINT UNSIGNED PRIMARY KEY,
                company_name VARCHAR(150) NOT NULL,
                logo_path VARCHAR(255) NULL,
                theme_color CHAR(7) NOT NULL DEFAULT "#1d4ed8",
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )'
        );
    }

    public function getSettings(): array
    {
        $stmt = $this->query('SELECT * FROM company_settings WHERE id = 1 LIMIT 1');
        $settings = $stmt->fetch();

        return $settings ?: [
            'id' => 1,
            'company_name' => '',
            'logo_path' => null,
            'theme_color' => '#1d4ed8',
        ];
    }

    public function saveSettings(string $companyName, string $themeColor, ?string $logoPath = null): void
    {
        $existing = $this->getSettings();
        $logoPath = $logoPath ?? ($existing['logo_path'] ?? null);

        $this->query(
            'INSERT INTO company_settings (id, company_name, logo_path, theme_color) VALUES (1, ?, ?, ?)
             ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), logo_path = VALUES(logo_path), theme_color = VALUES(theme_color)',
            [$companyName, $logoPath, $themeColor]
        );
    }
}
