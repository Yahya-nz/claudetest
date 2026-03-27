<?php
require_once 'includes/config.php';

$db = getDB();

// Get current month and year
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Validate month and year
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

// Get time slots
$timeSlotsStmt = $db->query("SELECT * FROM time_slots WHERE is_active = 1 ORDER BY start_time");
$timeSlots = $timeSlotsStmt->fetchAll();

// Get days in month
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$firstDayOfMonth = date('N', strtotime("$year-$month-01"));

// Calculate total weeks (7-day blocks from day 1, so week 1 = days 1-7, week 2 = days 8-14, etc.)
$totalWeeks = (int)ceil($daysInMonth / 7);

// Get week parameter — defaults to the week containing today (current month) or week 1 (other months)
if (isset($_GET['week'])) {
    $week = max(1, min((int)$_GET['week'], $totalWeeks));
} elseif ($month == (int)date('n') && $year == (int)date('Y')) {
    $week = (int)ceil((int)date('j') / 7);
} else {
    $week = 1;
}

// startDay/endDay for the current week view
$startDay = ($week - 1) * 7 + 1;
$endDay   = min($week * 7, $daysInMonth);

// Build prev-week navigation target
if ($week > 1) {
    $prevWeekMonth = $month; $prevWeekYear = $year; $prevWeekNum = $week - 1;
    $prevWeekLabel = '< Minggu ' . ($week - 1);
} else {
    $prevWeekMonth = $month - 1; $prevWeekYear = $year;
    if ($prevWeekMonth < 1) { $prevWeekMonth = 12; $prevWeekYear--; }
    $prevWeekDays  = cal_days_in_month(CAL_GREGORIAN, $prevWeekMonth, $prevWeekYear);
    $prevWeekNum   = (int)ceil($prevWeekDays / 7);
    $prevWeekLabel = '< Bulan Lalu';
}

// Build next-week navigation target
if ($week < $totalWeeks) {
    $nextWeekMonth = $month; $nextWeekYear = $year; $nextWeekNum = $week + 1;
    $nextWeekLabel = 'Minggu ' . ($week + 1) . ' >';
} else {
    $nextWeekMonth = $month + 1; $nextWeekYear = $year; $nextWeekNum = 1;
    if ($nextWeekMonth > 12) { $nextWeekMonth = 1; $nextWeekYear++; }
    $nextWeekLabel = 'Bulan Depan >';
}

// Today button target
$todayMonth = (int)date('n'); $todayYear = (int)date('Y');
$todayWeek  = (int)ceil((int)date('j') / 7);

// Get operation start date
$operationStartDateStmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'operation_start_date'");
$operationStartDateStmt->execute();
$operationStartDateResult = $operationStartDateStmt->fetch();
$operationStartDate = $operationStartDateResult ? $operationStartDateResult['setting_value'] : null;

// Get bookings for the month
$startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
$endDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-$daysInMonth";

// Get closed dates for the month
$closedDatesStmt = $db->prepare("SELECT closed_date, reason FROM closed_dates WHERE closed_date BETWEEN ? AND ? ORDER BY closed_date");
$closedDatesStmt->execute([$startDate, $endDate]);
$closedDatesData = $closedDatesStmt->fetchAll();

// Organize closed dates by date
$closedDates = [];
foreach ($closedDatesData as $closedDate) {
    $closedDates[$closedDate['closed_date']] = $closedDate['reason'];
}

$bookingsStmt = $db->prepare("
    SELECT b.booking_date, b.time_slot_id, b.status, t.start_time, t.end_time
    FROM bookings b
    JOIN time_slots t ON b.time_slot_id = t.id
    WHERE b.booking_date BETWEEN ? AND ?
    AND b.status IN ('pending', 'confirmed')
");
$bookingsStmt->execute([$startDate, $endDate]);
$bookingsData = $bookingsStmt->fetchAll();

// Organize bookings by date and time slot
$bookings = [];
foreach ($bookingsData as $booking) {
    $key = $booking['booking_date'] . '_' . $booking['time_slot_id'];
    $bookings[$key] = $booking['status'];
}

$monthNames = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal - <?php echo getSetting('site_name'); ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <section style="background: linear-gradient(135deg, var(--primary-700), var(--primary-900)); padding: 120px 0 60px; color: white;">
        <div class="container text-center">
            <h1 style="color: white; margin-bottom: var(--space-md);">Jadwal Lapangan</h1>
            <p style="opacity: 0.9; max-width: 500px; margin: 0 auto;">
                Lihat ketersediaan lapangan dan booking slot yang Anda inginkan
            </p>
        </div>
    </section>

    <!-- Schedule Section -->
    <section class="section">
        <div class="container">
            <?php if ($operationStartDate || !empty($closedDates)): ?>
            <!-- Operational Info Banner -->
            <div class="alert alert-info mb-4" style="background: linear-gradient(135deg, #dbeafe, #eff6ff); border-left: 4px solid #3b82f6; padding: var(--space-lg); border-radius: var(--radius-lg);">
                <div style="display: flex; align-items: start; gap: var(--space-md);">
                    <i data-lucide="info" width="24" height="24" style="color: #3b82f6; flex-shrink: 0; margin-top: 2px;"></i>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 var(--space-sm) 0; color: #1e40af; font-weight: 700;">Informasi Operasional</h4>
                        <?php if ($operationStartDate): ?>
                        <p style="margin: 0 0 var(--space-xs) 0; color: #1e40af;">
                            <strong>Lapangan mulai beroperasi pada:</strong>
                            <?php
                            $opStartTimestamp = strtotime($operationStartDate);
                            $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                            $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                            echo $dayNames[date('w', $opStartTimestamp)] . ', ' . date('d', $opStartTimestamp) . ' ' . $monthNames[(int)date('n', $opStartTimestamp)] . ' ' . date('Y', $opStartTimestamp);
                            ?>
                        </p>
                        <?php endif; ?>
                        <?php if (!empty($closedDates)): ?>
                        <p style="margin: var(--space-sm) 0 var(--space-xs) 0; color: #1e40af;"><strong>Tanggal libur bulan ini:</strong></p>
                        <ul style="margin: 0; padding-left: var(--space-lg); color: #1e40af;">
                            <?php
                            $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                            foreach ($closedDates as $date => $reason):
                                $timestamp = strtotime($date);
                            ?>
                            <li style="margin-bottom: var(--space-xs);">
                                <?php echo $dayNames[date('w', $timestamp)] . ', ' . date('d/m/Y', $timestamp); ?>
                                <?php if ($reason): ?>
                                - <em><?php echo htmlspecialchars($reason); ?></em>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
                <div class="legend-item">
                    <div class="legend-color maintenance"></div>
                    <span>Maintenance</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: var(--gray-200);"></div>
                    <span>Belum Buka</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #fbbf24;"></div>
                    <span>Libur</span>
                </div>
            </div>

            <!-- Schedule Container -->
            <div class="schedule-container">
                <!-- Schedule Header -->
                <div class="schedule-header">
                    <div class="d-flex align-center justify-between w-full flex-wrap gap-2">
                        <h3 style="color: white; margin: 0;">
                            <i data-lucide="calendar" width="24" height="24" style="vertical-align: middle; margin-right: 8px;"></i>
                            Jadwal <?php echo $monthNames[$month]; ?> <?php echo $year; ?>
                            <span style="font-size: 0.85rem; font-weight: 400; opacity: 0.85; margin-left: 8px;">- Minggu <?php echo $week; ?>/<?php echo $totalWeeks; ?></span>
                        </h3>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="?month=<?php echo $month == 1 ? 12 : $month - 1; ?>&year=<?php echo $month == 1 ? $year - 1 : $year; ?>&week=1" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white;">
                                <i data-lucide="chevrons-left" width="18" height="18"></i>
                                Bulan Lalu
                            </a>
                            <a href="?month=<?php echo $prevWeekMonth; ?>&year=<?php echo $prevWeekYear; ?>&week=<?php echo $prevWeekNum; ?>" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white;">
                                <?php echo $prevWeekLabel; ?>
                            </a>
                            <a href="?month=<?php echo $todayMonth; ?>&year=<?php echo $todayYear; ?>&week=<?php echo $todayWeek; ?>" class="btn btn-sm" style="background: rgba(255,255,255,0.3); color: white;">
                                <i data-lucide="calendar" width="18" height="18"></i>
                                Hari Ini
                            </a>
                            <a href="?month=<?php echo $nextWeekMonth; ?>&year=<?php echo $nextWeekYear; ?>&week=<?php echo $nextWeekNum; ?>" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white;">
                                <?php echo $nextWeekLabel; ?>
                            </a>
                            <a href="?month=<?php echo $month == 12 ? 1 : $month + 1; ?>&year=<?php echo $month == 12 ? $year + 1 : $year; ?>&week=1" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white;">
                                Bulan Depan
                                <i data-lucide="chevrons-right" width="18" height="18"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Calendar View -->
                <div style="overflow-x: auto;">
                    <table class="schedule-table" style="width: 100%; border-collapse: collapse; min-width: 800px;">
                        <thead>
                            <tr style="background: var(--primary-50);">
                                <th style="padding: var(--space-md); text-align: left; font-weight: 600; min-width: 100px; border-bottom: 2px solid var(--primary-200);">Jam</th>
                                <?php
                                $dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

                                for ($d = $startDay; $d <= $endDay; $d++):
                                    $dateStr = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                                    $dayOfWeek = date('N', strtotime($dateStr)) - 1;
                                    $isWeekend = $dayOfWeek >= 5;
                                ?>
                                <th style="padding: var(--space-md); text-align: center; min-width: 100px; border-bottom: 2px solid var(--primary-200); <?php echo $isWeekend ? 'background: var(--primary-100);' : ''; ?>">
                                    <div style="font-weight: 700; color: <?php echo $isWeekend ? 'var(--primary-700)' : 'var(--gray-700)'; ?>;"><?php echo $dayNames[$dayOfWeek]; ?></div>
                                    <div style="font-size: 1.25rem; font-weight: 800; color: var(--primary-600);"><?php echo $d; ?></div>
                                </th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($timeSlots as $slot): ?>
                            <tr>
                                <td style="padding: var(--space-md); font-weight: 600; border-bottom: 1px solid var(--gray-100);">
                                    <?php echo date('H:i', strtotime($slot['start_time'])); ?> - <?php echo date('H:i', strtotime($slot['end_time'])); ?>
                                </td>
                                <?php for ($d = $startDay; $d <= $endDay; $d++):
                                    $dateStr = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
                                    $key = $dateStr . '_' . $slot['id'];
                                    $status = isset($bookings[$key]) ? $bookings[$key] : 'available';
                                    $isPast = strtotime($dateStr) < strtotime(date('Y-m-d'));

                                    // Check if date is before operation start date
                                    $isBeforeOpening = $operationStartDate && strtotime($dateStr) < strtotime($operationStartDate);

                                    // Check if date is in closed dates
                                    $isClosed = isset($closedDates[$dateStr]);
                                    $closedReason = $isClosed ? $closedDates[$dateStr] : '';

                                    if ($isPast) {
                                        $status = 'disabled';
                                    } elseif ($isBeforeOpening) {
                                        $status = 'not_open_yet';
                                    } elseif ($isClosed) {
                                        $status = 'closed';
                                    }
                                ?>
                                <td style="padding: var(--space-sm); border-bottom: 1px solid var(--gray-100); text-align: center;">
                                    <?php if ($status === 'not_open_yet'): ?>
                                    <span class="time-slot" style="background: var(--gray-200); color: var(--gray-500); cursor: not-allowed;" title="Lapangan belum buka">
                                        Belum Buka
                                    </span>
                                    <?php elseif ($status === 'closed'): ?>
                                    <span class="time-slot" style="background: #fbbf24; color: #78350f; cursor: not-allowed;" title="<?php echo $closedReason ? htmlspecialchars($closedReason) : 'Libur'; ?>">
                                        Libur
                                    </span>
                                    <?php elseif ($status === 'available'): ?>
                                    <a href="booking.php?date=<?php echo $dateStr; ?>&slot=<?php echo $slot['id']; ?>" class="time-slot available">
                                        Tersedia
                                    </a>
                                    <?php elseif ($status === 'pending'): ?>
                                    <span class="time-slot pending">Pending</span>
                                    <?php elseif ($status === 'confirmed'): ?>
                                    <span class="time-slot booked">Booked</span>
                                    <?php else: ?>
                                    <span class="time-slot" style="background: var(--gray-100); color: var(--gray-400);">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endfor; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-body">
                    <div class="d-flex align-center justify-between flex-wrap gap-3">
                        <div>
                            <h4 class="mb-1">Ingin booking sekarang?</h4>
                            <p class="text-muted mb-0">Pilih tanggal dan jam yang tersedia, atau langsung booking melalui halaman booking</p>
                        </div>
                        <a href="booking.php" class="btn btn-primary">
                            <i data-lucide="calendar-plus" width="18" height="18"></i>
                            Booking Sekarang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script>
        lucide.createIcons();
        
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Mobile menu
        const menuToggle = document.getElementById('menuToggle');
        const mobileNav = document.getElementById('mobileNav');
        const mobileNavOverlay = document.getElementById('mobileNavOverlay');
        const mobileNavClose = document.getElementById('mobileNavClose');
        
        function openMobileNav() {
            mobileNav.classList.add('active');
            mobileNavOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeMobileNav() {
            mobileNav.classList.remove('active');
            mobileNavOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        menuToggle?.addEventListener('click', openMobileNav);
        mobileNavClose?.addEventListener('click', closeMobileNav);
        mobileNavOverlay?.addEventListener('click', closeMobileNav);
    </script>
</body>
</html>