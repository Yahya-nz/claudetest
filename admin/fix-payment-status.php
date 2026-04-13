<?php
require_once '../includes/config.php';
requireLogin();

$db = getDB();

try {
    // Add 'dp_paid' to payment_status ENUM
    $db->exec("ALTER TABLE bookings MODIFY COLUMN payment_status ENUM('unpaid','paid','dp_paid') NOT NULL DEFAULT 'unpaid'");
    echo "<p style='color:green;font-family:sans-serif;font-size:18px;'>&#10003; Berhasil! Kolom payment_status sudah diupdate. <a href='bookings.php'>Kembali ke Bookings</a></p>";
} catch (Exception $e) {
    echo "<p style='color:red;font-family:sans-serif;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
