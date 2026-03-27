<?php
require_once '../includes/config.php';
requireLogin();

$db = getDB();
$pageTitle = 'Jadwal';

// Get current week
$currentDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week', strtotime($currentDate)));
$weekEnd = date('Y-m-d', strtotime('sunday this week', strtotime($currentDate)));

// Get time slots
$timeSlots = $db->query("SELECT * FROM time_slots WHERE is_active = 1 ORDER BY start_time")->fetchAll();

// Get bookings for the week
$bookingsStmt = $db->prepare("
    SELECT b.*, t.start_time, t.end_time 
    FROM bookings b 
    JOIN time_slots t ON b.time_slot_id = t.id 
    WHERE b.booking_date BETWEEN ? AND ? 
    AND b.status IN ('pending', 'confirmed')
");
$bookingsStmt->execute([$weekStart, $weekEnd]);
$bookingsData = $bookingsStmt->fetchAll();

// Organize bookings
$bookings = [];
foreach ($bookingsData as $booking) {
    $key = $booking['booking_date'] . '_' . $booking['time_slot_id'];
    $bookings[$key] = $booking;
}

$dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
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
                        <h1 class="page-title">Jadwal Lapangan</h1>
                        <p class="page-subtitle">Lihat jadwal booking minggu ini</p>
                    </div>
                    <div class="page-actions">
                        <a href="?date=<?php echo date('Y-m-d', strtotime('-1 week', strtotime($currentDate))); ?>" class="btn btn-outline">
                            <i data-lucide="chevron-left" width="18" height="18"></i>
                            Minggu Lalu
                        </a>
                        <a href="?date=<?php echo date('Y-m-d'); ?>" class="btn btn-outline">Hari Ini</a>
                        <a href="?date=<?php echo date('Y-m-d', strtotime('+1 week', strtotime($currentDate))); ?>" class="btn btn-outline">
                            Minggu Depan
                            <i data-lucide="chevron-right" width="18" height="18"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Legend -->
                <div class="legend mb-4">
                    <div class="legend-item">
                        <div class="legend-color available"></div>
                        <span>Tersedia</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color pending"></div>
                        <span>Menunggu Pembayaran</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color booked"></div>
                        <span>Sudah Dibooking</span>
                    </div>
                </div>
                
                <!-- Schedule Table -->
                <div class="card">
                    <div class="card-header" style="background: var(--primary-600); color: white;">
                        <h3 class="mb-0" style="color: white;">
                            <i data-lucide="calendar" width="24" height="24" style="vertical-align: middle; margin-right: 8px;"></i>
                            <?php echo date('d M', strtotime($weekStart)); ?> - <?php echo date('d M Y', strtotime($weekEnd)); ?>
                        </h3>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table" style="min-width: 900px;">
                            <thead>
                                <tr>
                                    <th style="width: 100px;">Jam</th>
                                    <?php 
                                    for ($i = 0; $i < 7; $i++):
                                        $date = date('Y-m-d', strtotime("+$i days", strtotime($weekStart)));
                                        $isToday = $date === date('Y-m-d');
                                        $isWeekend = ($i >= 5);
                                    ?>
                                    <th style="text-align: center; <?php echo $isWeekend ? 'background: var(--primary-50);' : ''; ?> <?php echo $isToday ? 'background: var(--primary-100);' : ''; ?>">
                                        <div style="font-weight: 600;"><?php echo $dayNames[$i]; ?></div>
                                        <div style="font-size: 1.25rem; font-weight: 800; color: var(--primary-600);"><?php echo date('d', strtotime($date)); ?></div>
                                    </th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($timeSlots as $slot): ?>
                                <tr>
                                    <td style="font-weight: 600; white-space: nowrap;">
                                        <?php echo date('H:i', strtotime($slot['start_time'])); ?> - <?php echo date('H:i', strtotime($slot['end_time'])); ?>
                                    </td>
                                    <?php 
                                    for ($i = 0; $i < 7; $i++):
                                        $date = date('Y-m-d', strtotime("+$i days", strtotime($weekStart)));
                                        $key = $date . '_' . $slot['id'];
                                        $booking = isset($bookings[$key]) ? $bookings[$key] : null;
                                        $isPast = strtotime($date) < strtotime(date('Y-m-d'));
                                    ?>
                                    <td style="text-align: center; padding: var(--space-sm);">
                                        <?php if ($isPast && !$booking): ?>
                                            <span style="color: var(--gray-300);">-</span>
                                        <?php elseif ($booking): ?>
                                            <div style="background: <?php echo $booking['status'] === 'confirmed' ? 'var(--status-booked)' : 'var(--status-pending)'; ?>; color: white; padding: var(--space-xs) var(--space-sm); border-radius: var(--radius-md); font-size: 0.75rem;">
                                                <div style="font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100px;"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                                                <div style="opacity: 0.8;"><?php echo $booking['status'] === 'confirmed' ? 'Booked' : 'Pending'; ?></div>
                                            </div>
                                        <?php else: ?>
                                            <span class="time-slot available" style="display: inline-block; padding: var(--space-xs) var(--space-sm);">Tersedia</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endfor; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="d-grid mt-4" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-lg);">
                    <?php
                    $totalSlots = count($timeSlots) * 7;
                    $bookedSlots = count($bookings);
                    $availableSlots = $totalSlots - $bookedSlots;
                    $occupancyRate = $totalSlots > 0 ? round(($bookedSlots / $totalSlots) * 100) : 0;
                    ?>
                    <div class="card">
                        <div class="card-body text-center">
                            <div style="font-size: 2rem; font-weight: 800; color: var(--primary-600);"><?php echo $bookedSlots; ?></div>
                            <div class="text-muted">Slot Terisi</div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body text-center">
                            <div style="font-size: 2rem; font-weight: 800; color: var(--status-available);"><?php echo $availableSlots; ?></div>
                            <div class="text-muted">Slot Tersedia</div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body text-center">
                            <div style="font-size: 2rem; font-weight: 800; color: var(--accent-gold);"><?php echo $occupancyRate; ?>%</div>
                            <div class="text-muted">Tingkat Okupansi</div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/admin-scripts.php'; ?>
</body>
</html>