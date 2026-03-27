<?php
require_once '../includes/config.php';
requireLogin();

$db = getDB();

// Get statistics
$todayBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$pendingBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$confirmedBookings = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
$totalMembers = $db->query("SELECT COUNT(*) FROM members WHERE is_active = 1")->fetchColumn();

// Get monthly revenue
$monthlyRevenue = $db->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE status = 'confirmed' AND MONTH(booking_date) = MONTH(CURDATE()) AND YEAR(booking_date) = YEAR(CURDATE())")->fetchColumn();

// Get recent bookings
$recentBookingsStmt = $db->query("
    SELECT b.*, t.start_time, t.end_time 
    FROM bookings b 
    JOIN time_slots t ON b.time_slot_id = t.id 
    ORDER BY b.created_at DESC 
    LIMIT 10
");
$recentBookings = $recentBookingsStmt->fetchAll();

// Get today's schedule
$todayScheduleStmt = $db->prepare("
    SELECT b.*, t.start_time, t.end_time 
    FROM bookings b 
    JOIN time_slots t ON b.time_slot_id = t.id 
    WHERE b.booking_date = CURDATE() AND b.status IN ('pending', 'confirmed')
    ORDER BY t.start_time
");
$todayScheduleStmt->execute();
$todaySchedule = $todayScheduleStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    
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
                <!-- Page Header -->
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Dashboard</h1>
                        <p class="page-subtitle">Selamat datang kembali, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</p>
                    </div>
                    <div class="page-actions">
                        <a href="bookings.php?action=add" class="btn btn-primary">
                            <i data-lucide="plus" width="18" height="18"></i>
                            Booking Baru
                        </a>
                    </div>
                </div>
                
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-card-icon primary">
                            <i data-lucide="calendar-check" width="24" height="24"></i>
                        </div>
                        <div class="stat-card-content">
                            <div class="stat-card-value"><?php echo $todayBookings; ?></div>
                            <div class="stat-card-label">Booking Hari Ini</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-icon warning">
                            <i data-lucide="clock" width="24" height="24"></i>
                        </div>
                        <div class="stat-card-content">
                            <div class="stat-card-value"><?php echo $pendingBookings; ?></div>
                            <div class="stat-card-label">Menunggu Konfirmasi</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-icon info">
                            <i data-lucide="check-circle" width="24" height="24"></i>
                        </div>
                        <div class="stat-card-content">
                            <div class="stat-card-value"><?php echo $confirmedBookings; ?></div>
                            <div class="stat-card-label">Booking Terkonfirmasi</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-card-icon primary">
                            <i data-lucide="wallet" width="24" height="24"></i>
                        </div>
                        <div class="stat-card-content">
                            <div class="stat-card-value"><?php echo formatRupiah($monthlyRevenue); ?></div>
                            <div class="stat-card-label">Pendapatan Bulan Ini</div>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid" style="grid-template-columns: 2fr 1fr; gap: var(--space-xl);">
                    <!-- Recent Bookings -->
                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Booking Terbaru</h3>
                            <a href="bookings.php" class="btn btn-sm btn-outline">Lihat Semua</a>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentBookings)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted" style="padding: var(--space-xl);">Belum ada booking</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($recentBookings as $booking): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($booking['booking_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($booking['start_time'])); ?> - <?php echo date('H:i', strtotime($booking['end_time'])); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        $statusText = '';
                                        switch ($booking['status']) {
                                            case 'pending':
                                                $statusClass = 'badge-warning';
                                                $statusText = 'Pending';
                                                break;
                                            case 'confirmed':
                                                $statusClass = 'badge-success';
                                                $statusText = 'Confirmed';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'badge-danger';
                                                $statusText = 'Cancelled';
                                                break;
                                            case 'completed':
                                                $statusClass = 'badge-info';
                                                $statusText = 'Completed';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="booking-detail.php?id=<?php echo $booking['id']; ?>" class="action-btn action-btn-view" title="Lihat Detail">
                                                <i data-lucide="eye" width="16" height="16"></i>
                                            </a>
                                            <a href="bookings.php?action=edit&id=<?php echo $booking['id']; ?>" class="action-btn action-btn-edit" title="Edit">
                                                <i data-lucide="edit" width="16" height="16"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Today's Schedule -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">
                                <i data-lucide="calendar" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                Jadwal Hari Ini
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($todaySchedule)): ?>
                            <div class="empty-state" style="padding: var(--space-xl);">
                                <div class="empty-state-icon" style="width: 60px; height: 60px;">
                                    <i data-lucide="calendar-x" width="24" height="24"></i>
                                </div>
                                <p class="text-muted">Tidak ada jadwal hari ini</p>
                            </div>
                            <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: var(--space-sm);">
                                <?php foreach ($todaySchedule as $schedule): ?>
                                <div style="display: flex; align-items: center; gap: var(--space-md); padding: var(--space-md); background: var(--gray-50); border-radius: var(--radius-md);">
                                    <div style="width: 48px; height: 48px; background: <?php echo $schedule['status'] === 'confirmed' ? 'var(--primary-100)' : '#fef3c7'; ?>; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i data-lucide="clock" width="20" height="20" style="color: <?php echo $schedule['status'] === 'confirmed' ? 'var(--primary-600)' : '#d97706'; ?>;"></i>
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($schedule['customer_name']); ?></div>
                                        <div style="font-size: 0.875rem; color: var(--gray-500);">
                                            <?php echo date('H:i', strtotime($schedule['start_time'])); ?> - <?php echo date('H:i', strtotime($schedule['end_time'])); ?>
                                        </div>
                                    </div>
                                    <span class="badge <?php echo $schedule['status'] === 'confirmed' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo ucfirst($schedule['status']); ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/admin-scripts.php'; ?>

    <style>
        @media (max-width: 1024px) {
            .d-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</body>
</html>
