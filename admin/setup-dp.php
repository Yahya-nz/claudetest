<?php
/**
 * Setup script for DP (Down Payment) feature
 * Run this once to add required database columns and settings
 */
require_once '../includes/config.php';
requireLogin();

$db = getDB();
$messages = [];

try {
    // Add payment_type column to bookings table if not exists
    $checkColumn = $db->query("SHOW COLUMNS FROM bookings LIKE 'payment_type'");
    if ($checkColumn->rowCount() == 0) {
        $db->exec("ALTER TABLE bookings ADD COLUMN payment_type ENUM('full', 'dp') DEFAULT 'full' AFTER payment_status");
        $messages[] = ['type' => 'success', 'text' => 'Kolom payment_type berhasil ditambahkan ke tabel bookings'];
    } else {
        $messages[] = ['type' => 'info', 'text' => 'Kolom payment_type sudah ada'];
    }

    // Add paid_amount column to bookings table if not exists
    $checkColumn = $db->query("SHOW COLUMNS FROM bookings LIKE 'paid_amount'");
    if ($checkColumn->rowCount() == 0) {
        $db->exec("ALTER TABLE bookings ADD COLUMN paid_amount DECIMAL(10,2) DEFAULT 0 AFTER payment_type");
        $messages[] = ['type' => 'success', 'text' => 'Kolom paid_amount berhasil ditambahkan ke tabel bookings'];
    } else {
        $messages[] = ['type' => 'info', 'text' => 'Kolom paid_amount sudah ada'];
    }

    // Add dp_deadline column to bookings table if not exists
    $checkColumn = $db->query("SHOW COLUMNS FROM bookings LIKE 'dp_deadline'");
    if ($checkColumn->rowCount() == 0) {
        $db->exec("ALTER TABLE bookings ADD COLUMN dp_deadline DATE NULL AFTER paid_amount");
        $messages[] = ['type' => 'success', 'text' => 'Kolom dp_deadline berhasil ditambahkan ke tabel bookings'];
    } else {
        $messages[] = ['type' => 'info', 'text' => 'Kolom dp_deadline sudah ada'];
    }

    // Add DP settings if not exist
    $dpSettings = [
        ['dp_enabled', '0'],
        ['dp_percentage', '50'],
        ['dp_deadline_days', '1']
    ];

    foreach ($dpSettings as $setting) {
        $check = $db->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = ?");
        $check->execute([$setting[0]]);
        if ($check->fetchColumn() == 0) {
            $insert = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
            $insert->execute($setting);
            $messages[] = ['type' => 'success', 'text' => "Setting {$setting[0]} berhasil ditambahkan"];
        } else {
            $messages[] = ['type' => 'info', 'text' => "Setting {$setting[0]} sudah ada"];
        }
    }

    $messages[] = ['type' => 'success', 'text' => 'Setup DP selesai!'];

} catch (PDOException $e) {
    $messages[] = ['type' => 'error', 'text' => 'Error: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup DP Feature - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-main">
            <?php include 'includes/topbar.php'; ?>

            <div class="admin-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Setup Fitur DP (Down Payment)</h1>
                        <p class="page-subtitle">Hasil setup database untuk fitur DP</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <?php foreach ($messages as $msg): ?>
                        <div class="alert alert-<?php echo $msg['type'] === 'error' ? 'error' : ($msg['type'] === 'success' ? 'success' : 'info'); ?> mb-3">
                            <?php echo $msg['text']; ?>
                        </div>
                        <?php endforeach; ?>

                        <div style="margin-top: 20px;">
                            <a href="settings.php" class="btn btn-primary">
                                Ke Pengaturan
                            </a>
                            <a href="bookings.php" class="btn btn-outline" style="margin-left: 10px;">
                                Ke Kelola Booking
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>