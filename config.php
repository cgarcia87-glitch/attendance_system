<?php
/**
 * Global Configuration
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Manila');

// Root Directory
define('ROOT_PATH', dirname(__DIR__));

// PSR-4 / Class Autoloader for Models and Controllers
spl_autoload_register(function ($class) {
    if ($class === 'ClassModel') {
        require_once ROOT_PATH . '/models/Class.php';
        return;
    }
    $modelFile = ROOT_PATH . '/models/' . $class . '.php';
    if (file_exists($modelFile)) {
        require_once $modelFile;
        return;
    }
    $controllerFile = ROOT_PATH . '/controllers/' . $class . '.php';
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        return;
    }
});

// Dynamic Base URL Resolution
if (!defined('BASE_URL')) {
    if (php_sapi_name() === 'cli') {
        define('BASE_URL', 'http://localhost/attendance_management_system');
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        
        // Find project root folder name in SCRIPT_NAME
        $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $parts = explode('/', trim($scriptPath, '/'));
        
        // Check if top directory is attendance_management or attendance_management_system
        $baseFolder = '';
        if (isset($parts[0]) && in_array($parts[0], ['attendance_management', 'attendance_management_system'])) {
            $baseFolder = '/' . $parts[0];
        }
        
        define('BASE_URL', $protocol . $host . $baseFolder);
    }
}

// Load Database
require_once ROOT_PATH . '/config/database.php';

// System Settings Cache
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    define('APP_NAME', $settings['system_name'] ?? 'Attendance Management System');
    define('INSTITUTION_NAME', $settings['institution_name'] ?? 'Apex Institute of Technology');
    define('ACADEMIC_YEAR', $settings['academic_year'] ?? '2025-2026');
    define('CURRENT_SEMESTER', $settings['current_semester'] ?? '1st Semester');
    define('ALLOW_REGISTRATION', (bool)($settings['allow_registration'] ?? 1));
    define('LATE_THRESHOLD_MINUTES', (int)($settings['late_threshold_minutes'] ?? 15));
} catch (Exception $e) {
    define('APP_NAME', 'Attendance Management System');
    define('INSTITUTION_NAME', 'Apex Institute of Technology');
    define('ACADEMIC_YEAR', '2025-2026');
    define('CURRENT_SEMESTER', '1st Semester');
    define('ALLOW_REGISTRATION', true);
    define('LATE_THRESHOLD_MINUTES', 15);
}
