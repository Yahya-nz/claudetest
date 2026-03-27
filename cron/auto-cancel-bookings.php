<?php
/**
 * Auto-Cancel Expired Bookings
 *
 * This script automatically cancels bookings that have not been paid
 * within 20 minutes of creation.
 *
 * Setup Cron Job:
 * Add this line to your crontab (runs every 5 minutes):
 * */5 * * * * /usr/bin/php /path/to/Website-Minisoccer/cron/auto-cancel-bookings.php >> /path/to/logs/auto-cancel.log 2>&1
 *
 * Or run manually:
 * php /path/to/Website-Minisoccer/cron/auto-cancel-bookings.php
 */

// Auto-cancel feature has been disabled
exit(0);

// Only allow CLI execution
if (php_sapi_name() !== 'cli' && !isset($_GET['manual_run'])) {
    die('This script can only be run from the command line.');
}

require_once __DIR__ . '/../includes/config.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting auto-cancel process...\n";

try {
    $db = getDB();

    // Find all pending bookings older than 20 minutes
    $stmt = $db->prepare("
        SELECT id, booking_code, customer_name, customer_email, created_at
        FROM bookings
        WHERE status = 'pending'
        AND payment_status = 'unpaid'
        AND created_at < DATE_SUB(NOW(), INTERVAL 20 MINUTE)
    ");

    $stmt->execute();
    $expiredBookings = $stmt->fetchAll();

    if (empty($expiredBookings)) {
        echo "[" . date('Y-m-d H:i:s') . "] No expired bookings found.\n";
        exit(0);
    }

    echo "[" . date('Y-m-d H:i:s') . "] Found " . count($expiredBookings) . " expired booking(s).\n";

    // Cancel each expired booking
    $cancelStmt = $db->prepare("
        UPDATE bookings
        SET status = 'cancelled',
            payment_status = 'unpaid',
            updated_at = NOW()
        WHERE id = ?
    ");

    $cancelledCount = 0;

    foreach ($expiredBookings as $booking) {
        try {
            $cancelStmt->execute([$booking['id']]);
            $cancelledCount++;

            echo "[" . date('Y-m-d H:i:s') . "] ✓ Cancelled booking #{$booking['booking_code']} for {$booking['customer_name']} (Created: {$booking['created_at']})\n";

            // Optional: Log the cancellation
            if (function_exists('logNotification')) {
                logNotification(
                    $booking['id'],
                    'cancelled',
                    'system',
                    'success',
                    'Auto-cancelled after 20 minutes without payment'
                );
            }

        } catch (Exception $e) {
            echo "[" . date('Y-m-d H:i:s') . "] ✗ Failed to cancel booking #{$booking['booking_code']}: " . $e->getMessage() . "\n";
        }
    }

    echo "[" . date('Y-m-d H:i:s') . "] Completed. Cancelled {$cancelledCount}/{" . count($expiredBookings) . "} bookings.\n";
    echo str_repeat('-', 80) . "\n";

} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}