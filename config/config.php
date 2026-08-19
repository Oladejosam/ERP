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
define('DB_HOST', 'localhost');
define('DB_NAME', 'ERP_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('UPLOAD_DIR', APP_ROOT . '/uploads/');
define('ASSET_URL', BASE_URL . '/assets');
define('SESSION_TIMEOUT', 1800);
