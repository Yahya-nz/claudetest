<?php
require_once '../includes/config.php';
require_once '../includes/notifications.php';
requireLogin();

$db = getDB();
$pageTitle = 'Detail Booking';

// Get booking ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header('Location: bookings.php');
    exit;
}

// Handle actions - process then redirect to prevent duplicate execution on refresh
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // Get booking details for notifications
    $bookingStmt = $db->prepare("SELECT b.*, t.start_time, t.end_time FROM bookings b JOIN time_slots t ON b.time_slot_id = t.id WHERE b.id = ?");
    $bookingStmt->execute([$id]);
    $bookingForNotif = $bookingStmt->fetch();

    if ($action === 'confirm') {
        // Check if this is a DP booking
        $paymentType = $bookingForNotif['payment_type'] ?? 'full';

        if ($paymentType === 'dp') {
            // Confirm DP payment
            $stmt = $db->prepare("UPDATE bookings SET status = 'confirmed', payment_status = 'dp_paid' WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_message'] = 'DP berhasil dikonfirmasi. Ingatkan customer untuk melunasi sisa pembayaran.';
        } else {
            // Full payment confirm
            $stmt = $db->prepare("UPDATE bookings SET status = 'confirmed', payment_status = 'paid' WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_message'] = 'Booking berhasil dikonfirmasi.';
        }

        // Send confirmation notifications
        if ($bookingForNotif) {
            $notificationData = [
                'customer_name' => $bookingForNotif['customer_name'],
                'customer_email' => $bookingForNotif['customer_email'],
                'customer_phone' => $bookingForNotif['customer_phone'],
                'team_name' => $bookingForNotif['team_name'],
                'booking_code' => $bookingForNotif['booking_code'],
                'booking_date' => $bookingForNotif['booking_date'],
                'time_slot' => date('H:i', strtotime($bookingForNotif['start_time'])) . ' - ' . date('H:i', strtotime($bookingForNotif['end_time'])),
                'total_price' => $bookingForNotif['total_price'],
                'payment_type' => $bookingForNotif['payment_type'] ?? 'full',
                'paid_amount' => $bookingForNotif['paid_amount'] ?? 0,
                'dp_deadline' => $bookingForNotif['dp_deadline'] ?? null
            ];

            try {
                $notificationResults = sendBookingNotification($notificationData, 'confirmed');
                if ($notificationResults['email'] || (isset($notificationResults['whatsapp']['success']) && $notificationResults['whatsapp']['success'])) {
                    $_SESSION['flash_message'] .= ' Notifikasi telah dikirim ke customer.';
                }
            } catch (\Throwable $e) {
                error_log('confirm notification error: ' . $e->getMessage());
            }
        }
        $_SESSION['flash_type'] = 'success';

    } elseif ($action === 'cancel') {
        $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = 'Booking berhasil dibatalkan.';
        $_SESSION['flash_type'] = 'success';

    } elseif ($action === 'complete') {
        $stmt = $db->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?");
        $stmt->execute([$id]);

        // Send completion notifications
        if ($bookingForNotif) {
            $notificationData = [
                'customer_name' => $bookingForNotif['customer_name'],
                'customer_email' => $bookingForNotif['customer_email'],
                'customer_phone' => $bookingForNotif['customer_phone'],
                'team_name' => $bookingForNotif['team_name'],
                'booking_code' => $bookingForNotif['booking_code'],
                'booking_date' => $bookingForNotif['booking_date'],
                'time_slot' => date('H:i', strtotime($bookingForNotif['start_time'])) . ' - ' . date('H:i', strtotime($bookingForNotif['end_time'])),
                'total_price' => $bookingForNotif['total_price'],
                'payment_type' => $bookingForNotif['payment_type'] ?? 'full',
                'paid_amount' => $bookingForNotif['paid_amount'] ?? 0,
                'dp_deadline' => $bookingForNotif['dp_deadline'] ?? null
            ];

            try {
                $notificationResults = sendBookingNotification($notificationData, 'completed');
                $_SESSION['flash_message'] = 'Booking berhasil diselesaikan.';
                if ($notificationResults['email'] || (isset($notificationResults['whatsapp']['success']) && $notificationResults['whatsapp']['success'])) {
                    $_SESSION['flash_message'] .= ' Notifikasi telah dikirim ke customer.';
                }
            } catch (\Throwable $e) {
                error_log('completed notification error: ' . $e->getMessage());
                $_SESSION['flash_message'] = 'Booking berhasil diselesaikan.';
            }
        } else {
            $_SESSION['flash_message'] = 'Booking berhasil diselesaikan.';
        }
        $_SESSION['flash_type'] = 'success';

    } elseif ($action === 'mark-paid') {
        $stmt = $db->prepare("UPDATE bookings SET payment_status = 'paid', paid_amount = total_price WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = 'Pembayaran berhasil dilunasi.';
        $_SESSION['flash_type'] = 'success';
    }

    // Redirect to prevent duplicate action on refresh (PRG pattern)
    header('Location: booking-detail.php?id=' . $id);
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

// Get booking details
$stmt = $db->prepare("
    SELECT b.*, t.start_time, t.end_time,
           COALESCE(b.payment_type, 'full') as payment_type,
           COALESCE(b.paid_amount, 0) as paid_amount,
           b.dp_deadline
    FROM bookings b
    JOIN time_slots t ON b.time_slot_id = t.id
    WHERE b.id = ?
");
$stmt->execute([$id]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: bookings.php');
    exit;
}

// Get booking services
$servicesStmt = $db->prepare("
    SELECT bs.*, s.name, s.description
    FROM booking_services bs
    JOIN additional_services s ON bs.service_id = s.id
    WHERE bs.booking_id = ?
");
$servicesStmt->execute([$id]);
$bookingServices = $servicesStmt->fetchAll();

// Calculate service total
$servicesTotal = 0;
foreach ($bookingServices as $service) {
    $servicesTotal += $service['price'] * $service['quantity'];
}

// Check if weekend
$isWeekend = isWeekend($booking['booking_date']);
$dayType = $isWeekend ? 'Weekend' : 'Weekday';

// Calculate discount
$discount = $booking['base_price'] - ($booking['total_price'] - $servicesTotal);
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
                        <div class="d-flex align-center gap-2 mb-2">
                            <a href="bookings.php" class="btn btn-sm btn-outline" style="padding: 0.5rem;">
                                <i data-lucide="arrow-left" width="16" height="16"></i>
                            </a>
                            <h1 class="page-title" style="margin: 0;">Detail Booking</h1>
                        </div>
                        <p class="page-subtitle">Informasi lengkap pemesanan <?php echo htmlspecialchars($booking['booking_code']); ?></p>
                    </div>
                    <div class="page-actions">
                        <?php
                        $isDP = ($booking['payment_type'] ?? 'full') === 'dp';
                        $paidAmount = (float)($booking['paid_amount'] ?? 0);
                        $remainingAmount = $booking['total_price'] - $paidAmount;
                        ?>
                        <?php if ($booking['status'] === 'pending'): ?>
                            <?php if ($isDP): ?>
                            <a href="?id=<?php echo $id; ?>&action=confirm" class="btn btn-warning">
                                <i data-lucide="wallet" width="18" height="18"></i>
                                Konfirmasi DP
                            </a>
                            <?php else: ?>
                            <a href="?id=<?php echo $id; ?>&action=confirm" class="btn btn-primary">
                                <i data-lucide="check-circle" width="18" height="18"></i>
                                Konfirmasi
                            </a>
                            <?php endif; ?>
                        <a href="?id=<?php echo $id; ?>&action=cancel" class="btn btn-outline">
                            <i data-lucide="x-circle" width="18" height="18"></i>
                            Batalkan
                        </a>
                        <?php elseif ($booking['status'] === 'confirmed'): ?>
                            <?php if ($isDP && $booking['payment_status'] !== 'paid'): ?>
                            <a href="?id=<?php echo $id; ?>&action=mark-paid" class="btn btn-success">
                                <i data-lucide="banknote" width="18" height="18"></i>
                                Lunasi Sisa
                            </a>
                            <?php endif; ?>
                        <a href="?id=<?php echo $id; ?>&action=complete" class="btn btn-primary">
                            <i data-lucide="check-check" width="18" height="18"></i>
                            Selesai
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?> mb-4">
                    <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" width="20" height="20"></i>
                    <span><?php echo $message; ?></span>
                </div>
                <?php endif; ?>

                <div class="d-grid" style="grid-template-columns: 2fr 1fr; gap: var(--space-xl);">
                    <!-- Main Info -->
                    <div>
                        <!-- Booking Info Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i data-lucide="file-text" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                    Informasi Booking
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label class="info-label">Kode Booking</label>
                                        <div class="info-value">
                                            <strong style="font-size: 1.125rem; color: var(--primary-600);"><?php echo htmlspecialchars($booking['booking_code']); ?></strong>
                                        </div>
                                    </div>

                                    <div class="info-item">
                                        <label class="info-label">Tanggal Booking</label>
                                        <div class="info-value">
                                            <?php
                                            $dateObj = new DateTime($booking['booking_date']);
                                            echo $dateObj->format('l, d F Y');
                                            ?>
                                            <span class="badge <?php echo $isWeekend ? 'badge-warning' : 'badge-primary'; ?> ml-2">
                                                <?php echo $dayType; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="info-item">
                                        <label class="info-label">Jam Main</label>
                                        <div class="info-value">
                                            <i data-lucide="clock" width="16" height="16" style="vertical-align: middle; margin-right: 4px;"></i>
                                            <?php echo date('H:i', strtotime($booking['start_time'])); ?> - <?php echo date('H:i', strtotime($booking['end_time'])); ?>
                                            <span class="text-muted">(2 Jam)</span>
                                        </div>
                                    </div>

                                    <div class="info-item">
                                        <label class="info-label">Tipe Booking</label>
                                        <div class="info-value">
                                            <span class="badge <?php echo $booking['booking_type'] === 'member' ? 'badge-info' : 'badge-primary'; ?>">
                                                <?php echo $booking['booking_type'] === 'member' ? 'Member' : 'Regular'; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="info-item">
                                        <label class="info-label">Tanggal Dibuat</label>
                                        <div class="info-value text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Info Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i data-lucide="user" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                    Data Pemesan
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="info-grid">
                                    <div class="info-item">
                                        <label class="info-label">Nama Lengkap</label>
                                        <div class="info-value"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                                    </div>

                                    <div class="info-item">
                                        <label class="info-label">No. Telepon / WhatsApp</label>
                                        <div class="info-value">
                                            <i data-lucide="phone" width="16" height="16" style="vertical-align: middle; margin-right: 4px;"></i>
                                            <?php echo htmlspecialchars($booking['customer_phone']); ?>
                                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $booking['customer_phone']); ?>" target="_blank" class="btn btn-sm btn-outline ml-2">
                                                <i data-lucide="message-circle" width="14" height="14"></i>
                                                WhatsApp
                                            </a>
                                        </div>
                                    </div>

                                    <?php if ($booking['customer_email']): ?>
                                    <div class="info-item">
                                        <label class="info-label">Email</label>
                                        <div class="info-value">
                                            <i data-lucide="mail" width="16" height="16" style="vertical-align: middle; margin-right: 4px;"></i>
                                            <?php echo htmlspecialchars($booking['customer_email']); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($booking['team_name']): ?>
                                    <div class="info-item">
                                        <label class="info-label">Nama Tim</label>
                                        <div class="info-value">
                                            <i data-lucide="users" width="16" height="16" style="vertical-align: middle; margin-right: 4px;"></i>
                                            <?php echo htmlspecialchars($booking['team_name']); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Services Card -->
                        <?php if (!empty($bookingServices)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i data-lucide="plus-circle" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                    Layanan Tambahan
                                </h4>
                            </div>
                            <div class="card-body">
                                <table class="table-simple">
                                    <thead>
                                        <tr>
                                            <th>Layanan</th>
                                            <th>Qty</th>
                                            <th class="text-right">Harga</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookingServices as $service): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($service['name']); ?></div>
                                                <?php if ($service['description']): ?>
                                                <div style="font-size: 0.875rem; color: var(--gray-500);"><?php echo htmlspecialchars($service['description']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $service['quantity']; ?>x</td>
                                            <td class="text-right"><?php echo formatRupiah($service['price'] * $service['quantity']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Notes Card -->
                        <?php if ($booking['notes']): ?>
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i data-lucide="message-square" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                    Catatan
                                </h4>
                            </div>
                            <div class="card-body">
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar -->
                    <div>
                        <!-- Status Card -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i data-lucide="activity" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                    Status
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="info-label mb-2">Status Booking</label>
                                    <?php
                                    $statusClass = '';
                                    $statusText = '';
                                    $statusIcon = '';
                                    switch ($booking['status']) {
                                        case 'pending':
                                            $statusClass = 'badge-warning';
                                            $statusText = 'Menunggu Konfirmasi';
                                            $statusIcon = 'clock';
                                            break;
                                        case 'confirmed':
                                            $statusClass = 'badge-success';
                                            $statusText = 'Terkonfirmasi';
                                            $statusIcon = 'check-circle';
                                            break;
                                        case 'cancelled':
                                            $statusClass = 'badge-danger';
                                            $statusText = 'Dibatalkan';
                                            $statusIcon = 'x-circle';
                                            break;
                                        case 'completed':
                                            $statusClass = 'badge-info';
                                            $statusText = 'Selesai';
                                            $statusIcon = 'check-check';
                                            break;
                                    }
                                    ?>
                                    <div>
                                        <span class="badge <?php echo $statusClass; ?>" style="font-size: 0.9375rem; padding: 0.5rem 1rem;">
                                            <i data-lucide="<?php echo $statusIcon; ?>" width="16" height="16" style="vertical-align: middle; margin-right: 4px;"></i>
                                            <?php echo $statusText; ?>
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <label class="info-label mb-2">Status Pembayaran</label>
                                    <?php
                                    $paymentStatusClass = 'badge-warning';
                                    $paymentStatusText = 'Belum Lunas';
                                    $paymentStatusIcon = 'clock';

                                    if ($booking['payment_status'] === 'paid') {
                                        $paymentStatusClass = 'badge-success';
                                        $paymentStatusText = 'Lunas';
                                        $paymentStatusIcon = 'check';
                                    } elseif ($booking['payment_status'] === 'dp_paid') {
                                        $paymentStatusClass = 'badge-info';
                                        $paymentStatusText = 'DP Terbayar';
                                        $paymentStatusIcon = 'wallet';
                                    }
                                    ?>
                                    <div>
                                        <span class="badge <?php echo $paymentStatusClass; ?>" style="font-size: 0.9375rem; padding: 0.5rem 1rem;">
                                            <i data-lucide="<?php echo $paymentStatusIcon; ?>" width="16" height="16" style="vertical-align: middle; margin-right: 4px;"></i>
                                            <?php echo $paymentStatusText; ?>
                                        </span>
                                    </div>

                                    <?php if ($isDP): ?>
                                    <!-- DP Payment Info -->
                                    <div style="margin-top: 12px; padding: 12px; background: #fef3c7; border-radius: 8px; border-left: 3px solid #f59e0b;">
                                        <div style="font-size: 0.8125rem; color: #92400e;">
                                            <strong>Tipe: DP Rp100.000</strong>
                                        </div>
                                        <div style="font-size: 0.875rem; margin-top: 6px;">
                                            <span style="color: #78350f;">Dibayar:</span>
                                            <strong style="color: #d97706;"><?php echo formatRupiah($paidAmount); ?></strong>
                                        </div>
                                        <?php if ($booking['payment_status'] !== 'paid' && $remainingAmount > 0): ?>
                                        <div style="font-size: 0.875rem; margin-top: 4px;">
                                            <span style="color: #78350f;">Sisa:</span>
                                            <strong style="color: #dc2626;"><?php echo formatRupiah($remainingAmount); ?></strong>
                                        </div>
                                        <?php if ($booking['dp_deadline']): ?>
                                        <div style="font-size: 0.75rem; margin-top: 4px; color: <?php echo strtotime($booking['dp_deadline']) < strtotime('today') ? '#dc2626' : '#78350f'; ?>;">
                                            Deadline: <?php echo date('d M Y', strtotime($booking['dp_deadline'])); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($booking['payment_status'] !== 'paid'): ?>
                                    <a href="?id=<?php echo $id; ?>&action=mark-paid" class="btn btn-sm btn-primary mt-2 btn-block">
                                        <i data-lucide="check" width="14" height="14"></i>
                                        <?php echo $isDP ? 'Lunasi Sisa Pembayaran' : 'Tandai Lunas'; ?>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Price Summary Card -->
                        <div class="card">
                            <div class="card-header" style="background: var(--primary-50);">
                                <h4 class="mb-0" style="color: var(--primary-700);">
                                    <i data-lucide="receipt" width="20" height="20" style="vertical-align: middle; margin-right: 8px;"></i>
                                    Rincian Harga
                                </h4>
                            </div>
                            <div class="card-body">
                                <table class="price-summary-table">
                                    <tr>
                                        <td>Sewa Lapangan</td>
                                        <td class="text-right"><?php echo formatRupiah($booking['base_price']); ?></td>
                                    </tr>
                                    <?php if ($discount > 0): ?>
                                    <tr class="text-success">
                                        <td>Diskon Member (10%)</td>
                                        <td class="text-right">- <?php echo formatRupiah($discount); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($servicesTotal > 0): ?>
                                    <tr>
                                        <td>Layanan Tambahan</td>
                                        <td class="text-right"><?php echo formatRupiah($servicesTotal); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr class="price-total">
                                        <td><strong>Total Pembayaran</strong></td>
                                        <td class="text-right"><strong><?php echo formatRupiah($booking['total_price']); ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/admin-scripts.php'; ?>

    <style>
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-lg);
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: var(--space-xs);
        }

        .info-label {
            font-size: 0.875rem;
            color: var(--gray-500);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1rem;
            color: var(--gray-900);
            font-weight: 500;
        }

        .table-simple {
            width: 100%;
            border-collapse: collapse;
        }

        .table-simple th,
        .table-simple td {
            padding: var(--space-md);
            text-align: left;
            border-bottom: 1px solid var(--gray-100);
        }

        .table-simple th {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
            text-transform: uppercase;
        }

        .table-simple tbody tr:last-child td {
            border-bottom: none;
        }

        .price-summary-table {
            width: 100%;
        }

        .price-summary-table td {
            padding: var(--space-sm) 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .price-summary-table tr:last-child td {
            border-bottom: none;
        }

        .price-total {
            border-top: 2px solid var(--gray-200);
            padding-top: var(--space-md) !important;
        }

        .price-total td {
            padding-top: var(--space-md) !important;
            font-size: 1.125rem;
            color: var(--primary-600);
        }

        .text-success {
            color: var(--primary-600) !important;
        }

        @media (max-width: 1024px) {
            .d-grid {
                grid-template-columns: 1fr !important;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>