<?php
require_once '../includes/config.php';
require_once '../includes/notifications.php';
requireLogin();

$db = getDB();
$pageTitle = 'Kelola Booking';

// Handle actions - process then redirect to prevent duplicate execution on refresh
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($action === 'confirm' && $id > 0) {
        // Get booking details before updating
        $bookingStmt = $db->prepare("SELECT b.*, t.start_time, t.end_time FROM bookings b JOIN time_slots t ON b.time_slot_id = t.id WHERE b.id = ?");
        $bookingStmt->execute([$id]);
        $booking = $bookingStmt->fetch();

        // Update booking status
        $stmt = $db->prepare("UPDATE bookings SET status = 'confirmed', payment_status = 'paid' WHERE id = ?");
        $stmt->execute([$id]);

        // Send confirmation notifications
        if ($booking) {
            $notificationData = [
                'customer_name' => $booking['customer_name'],
                'customer_email' => $booking['customer_email'],
                'customer_phone' => $booking['customer_phone'],
                'team_name' => $booking['team_name'],
                'booking_code' => $booking['booking_code'],
                'booking_date' => $booking['booking_date'],
                'time_slot' => date('H:i', strtotime($booking['start_time'])) . ' - ' . date('H:i', strtotime($booking['end_time'])),
                'total_price' => $booking['total_price']
            ];

            try {
                $notificationResults = sendBookingNotification($notificationData, 'confirmed');
                if ($notificationResults['email']) {
                    logNotification($id, 'confirmed', 'email', 'success');
                }
                if (isset($notificationResults['whatsapp']['success']) && $notificationResults['whatsapp']['success']) {
                    logNotification($id, 'confirmed', 'whatsapp', 'success');
                }
            } catch (Exception $e) {
                error_log('confirm notification error: ' . $e->getMessage());
            }
        }

        $_SESSION['flash_message'] = 'Booking berhasil dikonfirmasi dan notifikasi telah dikirim ke customer.';
        $_SESSION['flash_type'] = 'success';
    } elseif ($action === 'cancel' && $id > 0) {
        $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = 'Booking berhasil dibatalkan.';
        $_SESSION['flash_type'] = 'success';
    } elseif ($action === 'complete' && $id > 0) {
        // Get booking details before updating
        $bookingStmt = $db->prepare("SELECT b.*, t.start_time, t.end_time FROM bookings b JOIN time_slots t ON b.time_slot_id = t.id WHERE b.id = ?");
        $bookingStmt->execute([$id]);
        $booking = $bookingStmt->fetch();

        // Update booking status
        $stmt = $db->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?");
        $stmt->execute([$id]);

        // Send completion notifications
        if ($booking) {
            $notificationData = [
                'customer_name' => $booking['customer_name'],
                'customer_email' => $booking['customer_email'],
                'customer_phone' => $booking['customer_phone'],
                'team_name' => $booking['team_name'],
                'booking_code' => $booking['booking_code'],
                'booking_date' => $booking['booking_date'],
                'time_slot' => date('H:i', strtotime($booking['start_time'])) . ' - ' . date('H:i', strtotime($booking['end_time'])),
                'total_price' => $booking['total_price']
            ];

            $notificationResults = sendBookingNotification($notificationData, 'completed');

            // Log notifications
            if ($notificationResults['email']) {
                logNotification($id, 'completed', 'email', 'success');
            }
            if (isset($notificationResults['whatsapp']['success']) && $notificationResults['whatsapp']['success']) {
                logNotification($id, 'completed', 'whatsapp', 'success');
            }
        }

        $_SESSION['flash_message'] = 'Booking berhasil diselesaikan dan notifikasi telah dikirim ke customer.';
        $_SESSION['flash_type'] = 'success';
    } elseif ($action === 'delete' && $id > 0) {
        $stmt = $db->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = 'Booking berhasil dihapus.';
        $_SESSION['flash_type'] = 'success';
    } elseif ($action === 'confirm_dp' && $id > 0) {
        // Confirm DP payment (booking confirmed but payment not fully paid yet)
        $bookingStmt = $db->prepare("SELECT b.*, t.start_time, t.end_time FROM bookings b JOIN time_slots t ON b.time_slot_id = t.id WHERE b.id = ?");
        $bookingStmt->execute([$id]);
        $booking = $bookingStmt->fetch();

        $stmt = $db->prepare("UPDATE bookings SET status = 'confirmed', payment_status = 'dp_paid' WHERE id = ?");
        $stmt->execute([$id]);

        // Send confirmation notifications
        if ($booking) {
            $notificationData = [
                'customer_name' => $booking['customer_name'],
                'customer_email' => $booking['customer_email'],
                'customer_phone' => $booking['customer_phone'],
                'team_name' => $booking['team_name'],
                'booking_code' => $booking['booking_code'],
                'booking_date' => $booking['booking_date'],
                'time_slot' => date('H:i', strtotime($booking['start_time'])) . ' - ' . date('H:i', strtotime($booking['end_time'])),
                'total_price' => $booking['total_price'],
                'payment_type' => $booking['payment_type'] ?? 'dp',
                'paid_amount' => $booking['paid_amount'] ?? 0,
                'dp_deadline' => $booking['dp_deadline'] ?? null
            ];

            try {
                $notificationResults = sendBookingNotification($notificationData, 'confirmed');
                if ($notificationResults['email']) {
                    logNotification($id, 'confirmed', 'email', 'success');
                }
                if (isset($notificationResults['whatsapp']['success']) && $notificationResults['whatsapp']['success']) {
                    logNotification($id, 'confirmed', 'whatsapp', 'success');
                }
            } catch (Exception $e) {
                error_log('confirm_dp notification error: ' . $e->getMessage());
            }
        }

        $_SESSION['flash_message'] = 'DP berhasil dikonfirmasi. Ingatkan customer untuk melunasi sisa pembayaran.';
        $_SESSION['flash_type'] = 'success';
    } elseif ($action === 'mark_paid' && $id > 0) {
        // Mark remaining payment as paid (for DP bookings)
        $stmt = $db->prepare("UPDATE bookings SET payment_status = 'paid', paid_amount = total_price WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = 'Pembayaran berhasil dilunasi.';
        $_SESSION['flash_type'] = 'success';
    }

    // Redirect to prevent duplicate action on refresh (PRG pattern)
    header('Location: bookings.php');
    exit;
}

// Get flash message from session (if any)
$message = '';
$messageType = '';
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';

// Build query - include payment_type, paid_amount, dp_deadline
$query = "SELECT b.*, t.start_time, t.end_time,
          COALESCE(b.payment_type, 'full') as payment_type,
          COALESCE(b.paid_amount, 0) as paid_amount,
          b.dp_deadline
          FROM bookings b JOIN time_slots t ON b.time_slot_id = t.id WHERE 1=1";
$params = [];

if ($statusFilter) {
    $query .= " AND b.status = ?";
    $params[] = $statusFilter;
}

if ($dateFilter) {
    $query .= " AND b.booking_date = ?";
    $params[] = $dateFilter;
}

$query .= " ORDER BY b.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
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
                        <h1 class="page-title">Kelola Booking</h1>
                        <p class="page-subtitle">Lihat dan kelola semua pemesanan lapangan</p>
                    </div>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?> mb-4">
                    <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" width="20" height="20"></i>
                    <span><?php echo $message; ?></span>
                </div>
                <?php endif; ?>

                <!-- DP Payment Guide -->
                <div class="card mb-4" style="border-left: 4px solid #f59e0b;">
                    <div class="card-body" style="padding: 16px;">
                        <div style="display: flex; align-items: start; gap: 12px;">
                            <i data-lucide="wallet" width="24" height="24" style="color: #d97706; flex-shrink: 0;"></i>
                            <div>
                                <h4 style="margin: 0 0 8px 0; font-size: 1rem; color: #92400e;">Panduan Pembayaran DP</h4>
                                <div style="font-size: 0.8125rem; color: #78350f;">
                                    <p style="margin: 0 0 4px 0;"><strong>Alur Booking DP:</strong></p>
                                    <ol style="margin: 0; padding-left: 20px;">
                                        <li><span class="badge badge-warning" style="font-size: 0.625rem;">Pending</span> Customer booking dengan DP, tunggu bukti transfer DP</li>
                                        <li><i data-lucide="wallet" width="12" height="12" style="vertical-align: middle;"></i> Klik tombol kuning untuk <strong>Konfirmasi DP</strong></li>
                                        <li><span class="badge badge-info" style="font-size: 0.625rem;">DP Terbayar</span> Ingatkan customer untuk melunasi sebelum deadline</li>
                                        <li><i data-lucide="banknote" width="12" height="12" style="vertical-align: middle;"></i> Setelah lunas, klik <strong>Lunasi Sisa</strong></li>
                                        <li><span class="badge badge-success" style="font-size: 0.625rem;">Lunas</span> Booking siap dimainkan</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="data-table-container">
                    <div class="data-table-header">
                        <h3 class="data-table-title">Daftar Booking</h3>
                        <div class="data-table-filters">
                            <form method="GET" class="d-flex gap-2">
                                <select name="status" class="filter-select" onchange="this.form.submit()">
                                    <option value="">Semua Status</option>
                                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                                <input type="date" name="date" class="filter-select" value="<?php echo $dateFilter; ?>" onchange="this.form.submit()">
                                <?php if ($statusFilter || $dateFilter): ?>
                                <a href="bookings.php" class="btn btn-sm btn-outline">Reset</a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                    
                    <div class="table-responsive" id="tableWrapper">
                        <table class="data-table compact-table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Pemesan</th>
                                    <th>Jadwal</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted" style="padding: var(--space-2xl);">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">
                                                <i data-lucide="inbox" width="32" height="32"></i>
                                            </div>
                                            <h4 class="empty-state-title">Tidak ada data</h4>
                                            <p class="empty-state-text">Belum ada booking yang sesuai dengan filter</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($bookings as $booking):
                                    $paymentType = $booking['payment_type'] ?? 'full';
                                    $paidAmount = (float)($booking['paid_amount'] ?? 0);
                                    $remainingAmount = $booking['total_price'] - $paidAmount;
                                ?>
                                <tr>
                                    <td>
                                        <a href="booking-detail.php?id=<?php echo $booking['id']; ?>" class="booking-code" title="<?php echo $booking['booking_code']; ?>">
                                            <?php echo substr($booking['booking_code'], -8); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="customer-info">
                                            <span class="customer-name"><?php echo htmlspecialchars($booking['customer_name']); ?></span>
                                            <span class="customer-phone"><?php echo htmlspecialchars($booking['customer_phone']); ?></span>
                                            <?php if ($booking['team_name']): ?>
                                            <span class="team-name"><?php echo htmlspecialchars($booking['team_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="schedule-info">
                                            <span class="schedule-date"><?php echo date('d/m/y', strtotime($booking['booking_date'])); ?></span>
                                            <span class="schedule-time"><?php echo date('H:i', strtotime($booking['start_time'])); ?>-<?php echo date('H:i', strtotime($booking['end_time'])); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="price-info">
                                            <span class="total-price"><?php echo formatRupiah($booking['total_price']); ?></span>
                                            <?php if ($paymentType === 'dp'): ?>
                                            <span class="payment-badge dp">DP</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        $statusText = '';
                                        switch ($booking['status']) {
                                            case 'pending': $statusClass = 'badge-warning'; $statusText = 'Pending'; break;
                                            case 'confirmed': $statusClass = 'badge-success'; $statusText = 'OK'; break;
                                            case 'cancelled': $statusClass = 'badge-danger'; $statusText = 'Batal'; break;
                                            case 'completed': $statusClass = 'badge-info'; $statusText = 'Selesai'; break;
                                        }
                                        $paymentStatusClass = 'badge-warning';
                                        $paymentStatusText = 'Belum';
                                        if ($booking['payment_status'] === 'paid') {
                                            $paymentStatusClass = 'badge-success';
                                            $paymentStatusText = 'Lunas';
                                        } elseif ($booking['payment_status'] === 'dp_paid') {
                                            $paymentStatusClass = 'badge-info';
                                            $paymentStatusText = 'DP OK';
                                        }
                                        ?>
                                        <div class="status-badges">
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                            <span class="badge <?php echo $paymentStatusClass; ?>"><?php echo $paymentStatusText; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <?php if ($paymentType === 'dp'): ?>
                                                <a href="?action=confirm_dp&id=<?php echo $booking['id']; ?>" class="action-btn action-btn-dp" title="Konfirmasi DP">
                                                    <i data-lucide="wallet" width="14" height="14"></i>
                                                </a>
                                                <?php else: ?>
                                                <a href="?action=confirm&id=<?php echo $booking['id']; ?>" class="action-btn action-btn-view" title="Konfirmasi">
                                                    <i data-lucide="check" width="14" height="14"></i>
                                                </a>
                                                <?php endif; ?>
                                                <a href="?action=cancel&id=<?php echo $booking['id']; ?>" class="action-btn action-btn-delete" title="Batalkan">
                                                    <i data-lucide="x" width="14" height="14"></i>
                                                </a>
                                            <?php elseif ($booking['status'] === 'confirmed'): ?>
                                                <?php if ($paymentType === 'dp' && $booking['payment_status'] !== 'paid'): ?>
                                                <a href="?action=mark_paid&id=<?php echo $booking['id']; ?>" class="action-btn action-btn-view" title="Lunasi">
                                                    <i data-lucide="banknote" width="14" height="14"></i>
                                                </a>
                                                <?php endif; ?>
                                                <a href="?action=complete&id=<?php echo $booking['id']; ?>" class="action-btn action-btn-view" title="Selesai">
                                                    <i data-lucide="check-check" width="14" height="14"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="?action=delete&id=<?php echo $booking['id']; ?>" class="action-btn action-btn-delete" title="Hapus">
                                                <i data-lucide="trash-2" width="14" height="14"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/admin-scripts.php'; ?>
</body>
</html>