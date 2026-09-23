<?php
/**
 * Global configuration for the ERP application.
 */
declare(strict_types=1);

session_start();

date_default_timezone_set('Africa/Lagos');

define('APP_ROOT', dirname(__DIR__));
define('BASE_URL', 'http://localhost/ERP/public');
define('APP_NAME', 'Construction ERP');
define('APP_VERSION', '1.0.0');
define('DB_HOST', getenv('ERP_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('ERP_DB_PORT') ?: '3306');
define('DB_NAME', getenv('ERP_DB_NAME') ?: 'erp_db');
define('DB_USER', getenv('ERP_DB_USER') ?: 'root');
define('DB_PASS', getenv('ERP_DB_PASS') ?: '');
define('UPLOAD_DIR', APP_ROOT . '/uploads/');
define('ASSET_URL', BASE_URL . '/assets');
define('SESSION_TIMEOUT', 1800);
