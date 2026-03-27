<?php
/**
 * Database Configuration
 * MINISOCCER GELORA GERUNG
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'rlzfzfmi_minisoccer_db');
define('DB_USER', 'rlzfzfmi_minisoccer_db');
define('DB_PASS', 'HH97HZ3nPqc8k2YnZFC3');
define('DB_CHARSET', 'utf8mb4');
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Session configuration
session_start();

// Timezone
date_default_timezone_set('Asia/Makassar');

// Site URL (auto-detect or set manually)
// Untuk production, ganti dengan domain Anda
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];

// Set base path - sesuaikan jika website ada di subfolder
if (strpos($_SERVER['REQUEST_URI'], '/minisoccer') !== false) {
    $basePath = '/minisoccer';
} else {
    $basePath = '';
}

define('SITE_URL', $protocol . $host . $basePath);
define('ADMIN_URL', SITE_URL . '/admin');

// Helper functions
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

function generateBookingCode() {
    return 'GG' . date('Ymd') . strtoupper(substr(uniqid(), -4));
}

function isWeekend($date) {
    $dayOfWeek = date('N', strtotime($date));
    if ($dayOfWeek >= 6) return true;

    // Check if date is a tanggal merah (public holiday) — uses weekend pricing
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM red_dates WHERE red_date = ?");
        $stmt->execute([$date]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        // Table may not exist yet, create it
        $db = getDB();
        $db->exec("
            CREATE TABLE IF NOT EXISTS red_dates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                red_date DATE NOT NULL UNIQUE,
                reason VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_red_date (red_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        return false;
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function getSetting($key) {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : '';
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}