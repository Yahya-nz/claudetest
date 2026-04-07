<?php
require_once 'includes/config.php';
require_once 'includes/notifications.php';

$db = getDB();

// Get operation start date
$operationStartDate = getSetting('operation_start_date') ?? '2026-02-02';

// Get DP settings
$dpEnabled = getSetting('dp_enabled') === '1';
$dpPercentage = (int)(getSetting('dp_percentage') ?? 50);
$dpDeadlineDays = (int)(getSetting('dp_deadline_days') ?? 1);

// DP availability will be checked per booking date (in JavaScript and during form submission)
// DP is available if: enabled AND booking date is NOT within any active promo period
// Default to false - will be dynamically updated via JavaScript when user selects a date
$dpAvailable = false;

// Get time slots
$timeSlotsStmt = $db->query("SELECT * FROM time_slots WHERE is_active = 1 ORDER BY start_time");
$timeSlots = $timeSlotsStmt->fetchAll();

// Function to get booked slots for a specific date (AJAX endpoint)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_booked_slots' && isset($_GET['date'])) {
    header('Content-Type: application/json');
    $date = sanitize($_GET['date']);
    $bookedStmt = $db->prepare("SELECT time_slot_id FROM bookings WHERE booking_date = ? AND status IN ('pending', 'confirmed')");
    $bookedStmt->execute([$date]);
    $bookedSlots = $bookedStmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['booked_slots' => $bookedSlots]);
    exit;
}

// AJAX endpoint to check if date has active promo (for DP availability)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'check_promo' && isset($_GET['date'])) {
    header('Content-Type: application/json');
    $date = sanitize($_GET['date']);

    // Check if date is within any active promo period
    $promoStmt = $db->prepare("SELECT name, discount_percent FROM promo_dates WHERE is_active = 1 AND ? BETWEEN date_from AND date_to ORDER BY discount_percent DESC LIMIT 1");
    $promoStmt->execute([$date]);
    $promo = $promoStmt->fetch();

    $hasPromo = $promo ? true : false;
    $dpAllowed = $dpEnabled && !$hasPromo;

    // Check if date is a tanggal merah (public holiday)
    $isRedDate = false;
    $redDate = null;
    try {
        $redDateStmt = $db->prepare("SELECT reason FROM red_dates WHERE red_date = ?");
        $redDateStmt->execute([$date]);
        $redDate = $redDateStmt->fetch();
        $isRedDate = $redDate ? true : false;
    } catch (PDOException $e) {
        // Table may not exist yet
    }

    echo json_encode([
        'has_promo' => $hasPromo,
        'promo_name' => $promo ? $promo['name'] : null,
        'promo_discount' => $promo ? $promo['discount_percent'] : 0,
        'dp_allowed' => $dpAllowed,
        'is_red_date' => $isRedDate,
        'red_date_reason' => $redDate ? $redDate['reason'] : null
    ]);
    exit;
}

// Get additional services
$servicesStmt = $db->query("SELECT * FROM additional_services WHERE is_active = 1 ORDER BY price");
$services = $servicesStmt->fetchAll();

// Get price settings
$priceSettingsStmt = $db->query("SELECT * FROM price_settings WHERE is_active = 1");
$priceSettings = $priceSettingsStmt->fetchAll();

// Organize prices
$prices = [];
foreach ($priceSettings as $ps) {
    $prices[$ps['day_type']][$ps['time_slot']] = $ps['price'];
}

$selectedDate = isset($_GET['date']) ? $_GET['date'] : '';
$selectedSlot = isset($_GET['slot']) ? (int)$_GET['slot'] : 0;

// Get active promos (for display - including upcoming promos with announcement date)
$today = date('Y-m-d');
$activePromosStmt = $db->prepare("
    SELECT *,
           CASE
               WHEN ? >= date_from AND ? <= date_to THEN 'active'
               WHEN ? >= COALESCE(announcement_date, date_from) AND ? < date_from THEN 'upcoming'
               ELSE 'inactive'
           END as promo_status
    FROM promo_dates
    WHERE is_active = 1
    AND (
        (? >= date_from AND ? <= date_to) OR
        (? >= COALESCE(announcement_date, date_from) AND ? < date_from)
    )
    ORDER BY date_from ASC, discount_percent DESC
");
$activePromosStmt->execute([$today, $today, $today, $today, $today, $today, $today, $today]);
$displayPromos = $activePromosStmt->fetchAll();

// Process booking
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = sanitize($_POST['customer_name'] ?? '');
    $customerPhone = sanitize($_POST['customer_phone'] ?? '');
    $customerEmail = sanitize($_POST['customer_email'] ?? '');
    $teamName = sanitize($_POST['team_name'] ?? '');
    $bookingDate = sanitize($_POST['booking_date'] ?? '');
    $timeSlotId = (int)($_POST['time_slot_id'] ?? 0);
    $bookingType = 'regular'; // Default booking type
    $selectedServices = $_POST['services'] ?? [];
    $paymentType = sanitize($_POST['payment_type'] ?? 'full');

    // Check if booking date is within promo period (DP not allowed during promo)
    $promoCheckStmt = $db->prepare("SELECT COUNT(*) FROM promo_dates WHERE is_active = 1 AND ? BETWEEN date_from AND date_to");
    $promoCheckStmt->execute([$bookingDate]);
    $bookingDateHasPromo = $promoCheckStmt->fetchColumn() > 0;

    // Validate payment type - only allow DP if enabled AND booking date is NOT in promo period
    if ($paymentType === 'dp' && (!$dpEnabled || $bookingDateHasPromo)) {
        $paymentType = 'full';
    }
    
    // Validation
    if (empty($customerName) || empty($customerPhone) || empty($bookingDate) || empty($timeSlotId)) {
        $message = 'Mohon lengkapi semua field yang wajib diisi.';
        $messageType = 'error';
    } else {
        // Check operation start date
        $operationStartDate = getSetting('operation_start_date') ?? '2026-02-02';
        if ($bookingDate < $operationStartDate) {
            $message = 'Maaf, lapangan belum buka untuk tanggal tersebut. Kami mulai beroperasi pada ' . date('d F Y', strtotime($operationStartDate)) . '.';
            $messageType = 'error';
        }
        // Check if date is closed
        else if ($closedCheck = $db->prepare("SELECT reason FROM closed_dates WHERE closed_date = ?")) {
            $closedCheck->execute([$bookingDate]);
            if ($closedReason = $closedCheck->fetch()) {
                $reason = $closedReason['reason'] ? ' (' . $closedReason['reason'] . ')' : '';
                $message = 'Maaf, lapangan tutup pada tanggal tersebut' . $reason . '. Silakan pilih tanggal lain.';
                $messageType = 'error';
            }
        }

        // Check if slot is available
        if (empty($message)) {
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND time_slot_id = ? AND status IN ('pending', 'confirmed')");
            $checkStmt->execute([$bookingDate, $timeSlotId]);

            if ($checkStmt->fetchColumn() > 0) {
                $message = 'Maaf, slot waktu ini sudah tidak tersedia.';
                $messageType = 'error';
            } else {
            // Get time slot info
            $slotStmt = $db->prepare("SELECT * FROM time_slots WHERE id = ?");
            $slotStmt->execute([$timeSlotId]);
            $slot = $slotStmt->fetch();
            
            // Calculate price
            $isWeekendDay = isWeekend($bookingDate);
            $dayType = $isWeekendDay ? 'weekend' : 'weekday';
            $timeSlotKey = substr($slot['start_time'], 0, 5) . '-' . substr($slot['end_time'], 0, 5);
            
            $basePrice = $prices[$dayType][$timeSlotKey] ?? 400000;

            // Check for active promo
            $promoDiscount = 0;
            $promoName = '';
            $promoStmt = $db->prepare("SELECT * FROM promo_dates WHERE is_active = 1 AND ? BETWEEN date_from AND date_to ORDER BY discount_percent DESC LIMIT 1");
            $promoStmt->execute([$bookingDate]);
            $activePromo = $promoStmt->fetch();

            if ($activePromo) {
                $promoDiscount = $basePrice * ($activePromo['discount_percent'] / 100);
                $promoName = $activePromo['name'];
            }

            // Apply promo discount only
            $discount = $promoDiscount;
            $discountType = 'promo';

            // Calculate services total
            $servicesTotal = 0;
            
            // Generate booking code
            $bookingCode = generateBookingCode();

            $totalPrice = $basePrice - $discount + $servicesTotal;

            // Calculate DP amount and deadline if DP payment
            $paidAmount = 0;
            $dpDeadline = null;
            if ($paymentType === 'dp') {
                $paidAmount = 100000; // DP flat Rp 100.000
                // Calculate deadline date
                $deadlineDate = new DateTime($bookingDate);
                $deadlineDate->modify("-{$dpDeadlineDays} days");
                $dpDeadline = $deadlineDate->format('Y-m-d');
            }

            // Insert booking
            $insertStmt = $db->prepare("
                INSERT INTO bookings (booking_code, customer_name, customer_phone, customer_email, team_name, booking_date, time_slot_id, booking_type, base_price, total_price, status, payment_status, payment_type, paid_amount, dp_deadline)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', ?, ?, ?)
            ");

            $insertStmt->execute([
                $bookingCode,
                $customerName,
                $customerPhone,
                $customerEmail,
                $teamName,
                $bookingDate,
                $timeSlotId,
                $bookingType,
                $basePrice,
                $totalPrice,
                $paymentType,
                $paidAmount,
                $dpDeadline
            ]);
            
            $bookingId = $db->lastInsertId();
            
            // Insert selected services
            if (!empty($selectedServices)) {
                $serviceStmt = $db->prepare("INSERT INTO booking_services (booking_id, service_id, quantity, price) VALUES (?, ?, 1, ?)");
                foreach ($selectedServices as $serviceId) {
                    $svcStmt = $db->prepare("SELECT price FROM additional_services WHERE id = ?");
                    $svcStmt->execute([$serviceId]);
                    $svcPrice = $svcStmt->fetchColumn();
                    $serviceStmt->execute([$bookingId, $serviceId, $svcPrice]);
                }
            }

            // Send notifications (Email + WhatsApp)
            $notificationData = [
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'team_name' => $teamName,
                'booking_code' => $bookingCode,
                'booking_date' => $bookingDate,
                'time_slot' => substr($slot['start_time'], 0, 5) . ' - ' . substr($slot['end_time'], 0, 5),
                'total_price' => $totalPrice,
                'payment_type' => $paymentType,
                'paid_amount' => $paidAmount,
                'dp_deadline' => $dpDeadline
            ];

            $notificationResults = sendBookingNotification($notificationData, 'created');

            // Log notifications
            if ($notificationResults['email']) {
                logNotification($bookingId, 'created', 'email', 'success');
            }
            if (isset($notificationResults['whatsapp']['success']) && $notificationResults['whatsapp']['success']) {
                logNotification($bookingId, 'created', 'whatsapp', 'success');
            }

            $message = "Booking berhasil! Kode booking Anda: <strong>$bookingCode</strong>.<br>";
            $message .= "Notifikasi telah dikirim ke ";

            $sentTo = [];
            if ($notificationResults['email']) {
                $sentTo[] = "email Anda";
            }
            if (isset($notificationResults['whatsapp']['success']) && $notificationResults['whatsapp']['success']) {
                $sentTo[] = "WhatsApp Anda";
            } elseif (isset($notificationResults['whatsapp']['manual']) && $notificationResults['whatsapp']['manual']) {
                $sentTo[] = "<a href='{$notificationResults['whatsapp']['link']}' target='_blank'>WhatsApp (klik di sini)</a>";
            }

            if (!empty($sentTo)) {
                $message .= implode(' dan ', $sentTo) . ".";
            }

            $message .= "<br><br>";

            // Show discount info if any
            if ($discount > 0) {
                $message .= "<div style='background-color: #f0fdf4; padding: 15px; border-radius: 8px; border-left: 4px solid #10b981; margin-top: 10px;'>";
                $message .= "<strong style='color: #166534;'>Discount Applied!</strong><br>";
                $message .= "<div style='margin-top: 8px; font-size: 14px;'>";
                $message .= "Harga Normal: <span style='text-decoration: line-through;'>" . formatRupiah($basePrice) . "</span><br>";
                if ($discountType === 'promo') {
                    $message .= "Promo: <strong>" . $promoName . "</strong> (-" . formatRupiah($discount) . ")<br>";
                } else {
                    $message .= "Member Discount 10%: -" . formatRupiah($discount) . "<br>";
                }
                $message .= "<strong>Total Setelah Diskon: " . formatRupiah($totalPrice) . "</strong>";
                $message .= "</div>";
                $message .= "</div>";
            }

            // Payment info based on payment type
            if ($paymentType === 'dp') {
                $message .= "<div style='background-color: #fef3c7; padding: 15px; border-radius: 8px; border-left: 4px solid #f59e0b; margin-top: 10px;'>";
                $message .= "<strong style='color: #92400e;'>Pembayaran DP (Rp100.000):</strong><br>";
                $message .= "<div style='margin-top: 10px; background-color: white; padding: 10px; border-radius: 4px;'>";
                $message .= "<strong>Bank:</strong> " . getSetting('bank_name') . "<br>";
                $message .= "<strong>No. Rekening:</strong> <span style='font-family: monospace; font-size: 16px;'>" . getSetting('bank_account_number') . "</span><br>";
                $message .= "<strong>Atas Nama:</strong> " . getSetting('bank_account_name') . "<br>";
                $message .= "<strong>Bayar Sekarang (DP):</strong> <span style='color: #d97706; font-size: 18px; font-weight: bold;'>" . formatRupiah($paidAmount) . "</span><br>";
                $message .= "<strong>Sisa Pembayaran:</strong> <span style='color: #dc2626; font-weight: bold;'>" . formatRupiah($totalPrice - $paidAmount) . "</span>";
                $message .= "</div>";
                $message .= "<p style='margin-top: 10px; margin-bottom: 0; color: #92400e; font-size: 13px;'>";
                $message .= "Segera transfer DP dan kirim bukti pembayaran. Sisa pembayaran <strong>wajib dilunasi sebelum " . date('d M Y', strtotime($dpDeadline)) . "</strong>.";
                $message .= "</p>";
                $message .= "</div>";
            } else {
                $message .= "<div style='background-color: #eff6ff; padding: 15px; border-radius: 8px; border-left: 4px solid #3b82f6; margin-top: 10px;'>";
                $message .= "<strong style='color: #1e40af;'>Transfer Pembayaran ke:</strong><br>";
                $message .= "<div style='margin-top: 10px; background-color: white; padding: 10px; border-radius: 4px;'>";
                $message .= "<strong>Bank:</strong> " . getSetting('bank_name') . "<br>";
                $message .= "<strong>No. Rekening:</strong> <span style='font-family: monospace; font-size: 16px;'>" . getSetting('bank_account_number') . "</span><br>";
                $message .= "<strong>Atas Nama:</strong> " . getSetting('bank_account_name') . "<br>";
                $message .= "<strong>Jumlah:</strong> <span style='color: #10b981; font-size: 18px; font-weight: bold;'>" . formatRupiah($totalPrice) . "</span>";
                $message .= "</div>";
                $message .= "<p style='margin-top: 10px; margin-bottom: 0; color: #1e40af; font-size: 13px;'>";
                $message .= "Segera transfer dan kirim bukti transfer ke WhatsApp/Email kami.";
                $message .= "</p>";
                $message .= "</div>";
            }
            $messageType = 'success';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking - <?php echo getSetting('site_name'); ?></title>
    
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
            <h1 style="color: white; margin-bottom: var(--space-md);">Booking Lapangan</h1>
            <p style="opacity: 0.9; max-width: 500px; margin: 0 auto;">
                Isi form di bawah untuk melakukan pemesanan lapangan
            </p>
        </div>
    </section>

    <!-- Booking Form Section -->
    <section class="section">
        <div class="container">
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?> mb-4">
                <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" width="20" height="20"></i>
                <div><?php echo $message; ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($displayPromos)): ?>
            <!-- Promo Banner -->
            <?php foreach ($displayPromos as $promo):
                $isUpcoming = $promo['promo_status'] === 'upcoming';
                $customText = !empty($promo['announcement_text']) ? $promo['announcement_text'] : null;
            ?>
            <div class="alert mb-4" style="background: linear-gradient(135deg, <?php echo $isUpcoming ? '#3b82f6, #2563eb' : '#fbbf24, #f59e0b'; ?>); border: none; color: white; box-shadow: 0 4px 12px rgba(<?php echo $isUpcoming ? '59, 130, 246' : '245, 158, 11'; ?>, 0.3);">
                <div style="display: flex; align-items: center; gap: var(--space-md);">
                    <i data-lucide="<?php echo $isUpcoming ? 'calendar-clock' : 'zap'; ?>" width="32" height="32" style="flex-shrink: 0;"></i>
                    <div style="flex: 1;">
                        <?php if ($isUpcoming): ?>
                            <h3 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: white;">
                                <?php echo $customText ? htmlspecialchars($customText) : 'Segera Hadir! ' . htmlspecialchars($promo['name']); ?>!
                            </h3>
                            <p style="margin: 0; font-size: 14px; opacity: 0.95;">
                                Promo spesial diskon <strong><?php echo $promo['discount_percent']; ?>%</strong> grand opening
                                <strong><?php echo date('d M Y', strtotime($promo['date_from'])); ?></strong>
                                sampai <strong><?php echo date('d M Y', strtotime($promo['date_to'])); ?></strong>.
                                Booking sekarang untuk tanggal tersebut!
                            </p>
                        <?php else: ?>
                            <h3 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: white;"><?php echo htmlspecialchars($promo['name']); ?>!</h3>
                            <p style="margin: 0; font-size: 14px; opacity: 0.95;">
                                Dapatkan diskon <strong><?php echo $promo['discount_percent']; ?>%</strong> untuk booking tanggal
                                <strong><?php echo date('d M', strtotime($promo['date_from'])); ?></strong> -
                                <strong><?php echo date('d M Y', strtotime($promo['date_to'])); ?></strong>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <div class="d-grid" style="grid-template-columns: 2fr 1fr; gap: var(--space-xl);">
                <!-- Booking Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">
                            <i data-lucide="clipboard-list" width="24" height="24" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                            Form Pemesanan
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="bookingForm">
                            <!-- Customer Info -->
                            <div class="mb-4">
                                <h4 class="mb-3" style="color: var(--primary-700);">
                                    <i data-lucide="user" width="20" height="20" style="vertical-align: middle; margin-right: 8px;"></i>
                                    Data Pemesan
                                </h4>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Nama Lengkap *</label>
                                        <input type="text" name="customer_name" class="form-input" required placeholder="Masukkan nama lengkap">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">No. Telepon / WhatsApp *</label>
                                        <input type="tel" name="customer_phone" class="form-input" required placeholder="08xxxxxxxxxx">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="customer_email" class="form-input" placeholder="email@example.com">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Nama Tim</label>
                                        <input type="text" name="team_name" class="form-input" placeholder="Nama tim Anda">
                                    </div>
                                </div>
                            </div>

                            <!-- Booking Info -->
                            <div class="mb-4">
                                <h4 class="mb-3" style="color: var(--primary-700);">
                                    <i data-lucide="calendar" width="20" height="20" style="vertical-align: middle; margin-right: 8px;"></i>
                                    Detail Pemesanan
                                </h4>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Tanggal Booking *</label>
                                        <input type="date" name="booking_date" id="bookingDate" class="form-input" required
                                               value="<?php echo $selectedDate; ?>"
                                               min="<?php echo $operationStartDate; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Jam Main *</label>
                                        <select name="time_slot_id" id="timeSlotId" class="form-select" required>
                                            <option value="">Pilih jam main</option>
                                            <?php foreach ($timeSlots as $slot): ?>
                                            <option value="<?php echo $slot['id']; ?>" <?php echo $selectedSlot == $slot['id'] ? 'selected' : ''; ?>>
                                                <?php echo date('H:i', strtotime($slot['start_time'])); ?> - <?php echo date('H:i', strtotime($slot['end_time'])); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Services — info only, order via WA -->
                            <?php if (!empty($services)): ?>
                            <div class="mb-4">
                                <h4 class="mb-3" style="color: var(--primary-700);">
                                    <i data-lucide="plus-circle" width="20" height="20" style="vertical-align: middle; margin-right: 8px;"></i>
                                    Sewa Tambahan
                                </h4>
                                <div class="d-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-md); margin-bottom: var(--space-md);">
                                    <?php foreach ($services as $service): ?>
                                    <div style="padding: var(--space-md); background: var(--gray-50); border-radius: var(--radius-lg); border: 1px solid var(--gray-200);">
                                        <div style="font-weight: 600; color: var(--gray-800);"><?php echo htmlspecialchars($service['name']); ?></div>
                                        <div style="color: var(--primary-600); font-weight: 700; margin-top: 4px;"><?php echo formatRupiah($service['price']); ?></div>
                                        <?php if ($service['description']): ?>
                                        <div style="font-size: 0.8rem; color: var(--gray-500); margin-top: 4px;"><?php echo htmlspecialchars($service['description']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php
                                $waNumber = preg_replace('/\D/', '', getSetting('site_whatsapp') ?? '');
                                $waText = urlencode('Halo, saya ingin memesan sewa tambahan bersamaan dengan booking lapangan saya.');
                                ?>
                                <a href="https://wa.me/<?php echo $waNumber; ?>?text=<?php echo $waText; ?>" target="_blank"
                                   style="display: inline-flex; align-items: center; gap: 8px; background: #25d366; color: white; padding: 10px 20px; border-radius: var(--radius-lg); font-weight: 600; text-decoration: none; font-size: 0.9rem;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    Pesan Sewa Tambahan via WhatsApp
                                </a>
                            </div>
                            <?php endif; ?>


                            <!-- Payment Type Selection -->
                            <div class="mb-4" id="paymentTypeSection">
                                <h4 class="mb-3" style="color: var(--primary-700);">
                                    <i data-lucide="wallet" width="20" height="20" style="vertical-align: middle; margin-right: 8px;"></i>
                                    Metode Pembayaran
                                </h4>

                                <!-- DP Available Section (hidden by default, shown via JS when date allows DP) -->
                                <div id="dpAvailableSection" style="display: none;">
                                    <div class="d-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-md);">
                                        <!-- Full Payment Option -->
                                        <label class="payment-option" style="padding: var(--space-lg); background: var(--gray-50); border: 2px solid var(--gray-200); border-radius: var(--radius-lg); cursor: pointer; transition: all 0.2s; display: block;">
                                            <input type="radio" name="payment_type" value="full" checked style="display: none;">
                                            <div style="display: flex; align-items: start; gap: var(--space-md);">
                                                <div class="payment-radio" style="width: 24px; height: 24px; border: 2px solid var(--gray-300); border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; margin-top: 2px;">
                                                    <div class="payment-radio-inner" style="width: 12px; height: 12px; background: var(--primary-500); border-radius: 50%; display: none;"></div>
                                                </div>
                                                <div style="flex: 1;">
                                                    <div style="font-weight: 700; font-size: 1rem; color: var(--gray-800); margin-bottom: 4px;">
                                                        <i data-lucide="check-circle" width="18" height="18" style="vertical-align: middle; margin-right: 4px; color: var(--primary-500);"></i>
                                                        Minimal DP 100k
                                                    </div>
                                                    <div style="color: var(--gray-600); font-size: 0.875rem; margin-bottom: 8px;">
                                                        DP minimal Rp 100.000, booking langsung dikonfirmasi
                                                    </div>
                                                    <div style="background: var(--primary-50); padding: 8px 12px; border-radius: var(--radius-md); display: inline-block;">
                                                        <span style="font-weight: 700; color: var(--primary-600);" id="fullPaymentAmount">Rp 0</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>

                                        <!-- DP Payment Option -->
                                        <label class="payment-option" style="padding: var(--space-lg); background: var(--gray-50); border: 2px solid var(--gray-200); border-radius: var(--radius-lg); cursor: pointer; transition: all 0.2s; display: block;">
                                            <input type="radio" name="payment_type" value="dp" style="display: none;">
                                            <div style="display: flex; align-items: start; gap: var(--space-md);">
                                                <div class="payment-radio" style="width: 24px; height: 24px; border: 2px solid var(--gray-300); border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; margin-top: 2px;">
                                                    <div class="payment-radio-inner" style="width: 12px; height: 12px; background: var(--primary-500); border-radius: 50%; display: none;"></div>
                                                </div>
                                                <div style="flex: 1;">
                                                    <div style="font-weight: 700; font-size: 1rem; color: var(--gray-800); margin-bottom: 4px;">
                                                        <i data-lucide="clock" width="18" height="18" style="vertical-align: middle; margin-right: 4px; color: #f59e0b;"></i>
                                                        DP Rp100.000
                                                    </div>
                                                    <div style="color: var(--gray-600); font-size: 0.875rem; margin-bottom: 8px;">
                                                        Bayar Rp100.000 dulu, sisanya <?php echo $dpDeadlineDays == 0 ? 'di hari H' : $dpDeadlineDays . ' hari sebelum main'; ?>
                                                    </div>
                                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                        <div style="background: #fef3c7; padding: 8px 12px; border-radius: var(--radius-md);">
                                                            <span style="font-size: 0.75rem; color: #92400e;">Bayar Sekarang:</span><br>
                                                            <span style="font-weight: 700; color: #d97706;" id="dpAmount">Rp 0</span>
                                                        </div>
                                                        <div style="background: var(--gray-100); padding: 8px 12px; border-radius: var(--radius-md);">
                                                            <span style="font-size: 0.75rem; color: var(--gray-500);">Sisa:</span><br>
                                                            <span style="font-weight: 600; color: var(--gray-600);" id="remainingAmount">Rp 0</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="alert alert-info mt-3" style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px;">
                                        <p style="margin: 0; font-size: 0.8125rem; color: #1e40af;">
                                            <i data-lucide="info" width="16" height="16" style="vertical-align: middle; margin-right: 4px;"></i>
                                            <strong>Penting:</strong> Jika memilih DP, sisa pembayaran <strong>wajib dilunasi <?php echo $dpDeadlineDays == 0 ? 'sebelum jam main' : $dpDeadlineDays . ' hari sebelum tanggal main'; ?></strong>.
                                            Booking akan dibatalkan otomatis jika tidak dilunasi tepat waktu.
                                        </p>
                                    </div>
                                </div>

                                <!-- Full Payment Only Section (shown by default, hidden when DP is available) -->
                                <div id="fullPaymentOnlySection">
                                    <input type="hidden" name="payment_type" id="paymentTypeFallback" value="full">
                                    <div style="padding: var(--space-lg); background: linear-gradient(135deg, var(--primary-50), var(--primary-100)); border: 2px solid var(--primary-200); border-radius: var(--radius-lg);">
                                        <div style="display: flex; align-items: center; gap: var(--space-md);">
                                            <div style="width: 48px; height: 48px; background: var(--primary-500); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                <i data-lucide="credit-card" width="24" height="24" style="color: white;"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight: 700; font-size: 1rem; color: var(--primary-700);">Minimal DP 100k</div>
                                                <div id="fullPaymentOnlyDesc" style="color: var(--primary-600); font-size: 0.875rem;">
                                                    DP minimal Rp 100.000 untuk konfirmasi booking
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="promoActiveAlert" class="alert alert-success mt-3" style="background: #f0fdf4; border-left: 4px solid #22c55e; padding: 12px; display: none;">
                                        <p style="margin: 0; font-size: 0.8125rem; color: #166534;">
                                            <i data-lucide="sparkles" width="16" height="16" style="vertical-align: middle; margin-right: 4px;"></i>
                                            <strong>Promo Aktif!</strong> <span id="promoAlertText">Nikmati harga spesial dengan pembayaran lunas.</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                <i data-lucide="check-circle" width="20" height="20"></i>
                                Konfirmasi Booking
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Booking Summary -->
                <div>
                    <div class="card" style="position: sticky; top: 100px;">
                        <div class="card-header" style="background: var(--primary-50);">
                            <h4 class="mb-0" style="color: var(--primary-700);">
                                <i data-lucide="receipt" width="20" height="20" style="vertical-align: middle; margin-right: 8px;"></i>
                                Ringkasan Booking
                            </h4>
                        </div>
                        <div class="card-body">
                            <div id="summaryContent">
                                <p class="text-muted text-center">Pilih tanggal dan jam untuk melihat ringkasan</p>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-between align-center">
                                <span style="font-weight: 600;">Total</span>
                                <span id="totalPrice" style="font-size: 1.5rem; font-weight: 800; color: var(--primary-600);">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="card mt-3">
                        <div class="card-body">
                            <h5 class="mb-3">
                                <i data-lucide="info" width="18" height="18" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                Ketentuan Booking
                            </h5>
                            <ul style="padding-left: var(--space-lg); color: var(--gray-600); font-size: 0.875rem;">
                                <li style="margin-bottom: var(--space-sm);">Minimal DP Rp 100.000 untuk konfirmasi booking</li>
                                <li style="margin-bottom: var(--space-sm);">Pemesanan minimal dilakukan 1 jam sebelum waktu main</li>
                                <li>Harga sudah termasuk pajak 10%</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script>
        lucide.createIcons();

        // Price data from PHP
        const prices = <?php echo json_encode($prices); ?>;
        const timeSlots = <?php echo json_encode($timeSlots); ?>;
        const dpAmount = 100000; // DP flat Rp 100.000
        const dpEnabledSetting = <?php echo $dpEnabled ? 'true' : 'false'; ?>;

        // Track current DP availability (changes based on selected date)
        let dpAvailableForDate = false;
        let currentPromoInfo = null;
        let currentDateIsRedDate = false;

        // Check if selected date has promo (and thus DP availability) and red date status
        async function checkPromoForDate(date) {
            if (!date) {
                dpAvailableForDate = false;
                currentPromoInfo = null;
                currentDateIsRedDate = false;
                updatePaymentSectionVisibility();
                return;
            }

            try {
                const response = await fetch(`?ajax=check_promo&date=${date}`);
                const data = await response.json();

                dpAvailableForDate = data.dp_allowed;
                currentPromoInfo = data.has_promo ? {
                    name: data.promo_name,
                    discount: data.promo_discount
                } : null;
                currentDateIsRedDate = data.is_red_date || false;

                updatePaymentSectionVisibility();
            } catch (error) {
                console.error('Error checking promo:', error);
                dpAvailableForDate = false;
                currentPromoInfo = null;
                currentDateIsRedDate = false;
                updatePaymentSectionVisibility();
            }
        }

        // Update payment section visibility based on DP availability
        function updatePaymentSectionVisibility() {
            const dpSection = document.getElementById('dpAvailableSection');
            const fullOnlySection = document.getElementById('fullPaymentOnlySection');
            const fullOnlyDesc = document.getElementById('fullPaymentOnlyDesc');
            const promoAlert = document.getElementById('promoActiveAlert');
            const promoAlertText = document.getElementById('promoAlertText');
            const paymentTypeFallback = document.getElementById('paymentTypeFallback');

            if (dpAvailableForDate) {
                // Show DP option
                dpSection.style.display = 'block';
                fullOnlySection.style.display = 'none';

                // Disable the fallback hidden input so radio buttons work
                if (paymentTypeFallback) paymentTypeFallback.disabled = true;

                // Ensure full payment is selected by default
                const fullPaymentRadio = document.querySelector('input[name="payment_type"][value="full"]');
                if (fullPaymentRadio) fullPaymentRadio.checked = true;
            } else {
                // Show full payment only
                dpSection.style.display = 'none';
                fullOnlySection.style.display = 'block';

                // Enable the fallback hidden input for full payment
                if (paymentTypeFallback) paymentTypeFallback.disabled = false;

                // Update description based on promo status
                if (currentPromoInfo) {
                    fullOnlyDesc.textContent = 'Selama periode promo, pembayaran dilakukan secara penuh';
                    promoAlert.style.display = 'block';
                    promoAlertText.textContent = `Diskon ${currentPromoInfo.discount}% dari ${currentPromoInfo.name}! Nikmati harga spesial dengan pembayaran lunas.`;
                } else if (!dpEnabledSetting) {
                    fullOnlyDesc.textContent = 'Pembayaran penuh untuk konfirmasi booking';
                    promoAlert.style.display = 'none';
                } else {
                    fullOnlyDesc.textContent = 'Pembayaran penuh untuk konfirmasi booking';
                    promoAlert.style.display = 'none';
                }
            }

            // Re-create lucide icons for newly visible elements
            lucide.createIcons();
        }

        // Filter and update available time slots
        async function updateAvailableTimeSlots() {
            const dateInput = document.getElementById('bookingDate');
            const slotSelect = document.getElementById('timeSlotId');
            const selectedDate = dateInput.value;

            if (!selectedDate) {
                // Reset to all slots if no date selected
                renderTimeSlots([]);
                return;
            }

            // Get current date and time
            const now = new Date();
            const today = now.toISOString().split('T')[0];
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();
            const currentTimeInMinutes = currentHour * 60 + currentMinute;

            // Fetch booked slots for selected date
            let bookedSlots = [];
            try {
                const response = await fetch(`?ajax=get_booked_slots&date=${selectedDate}`);
                const data = await response.json();
                bookedSlots = data.booked_slots || [];
            } catch (error) {
                console.error('Error fetching booked slots:', error);
            }

            // Filter time slots
            const availableSlots = timeSlots.filter(slot => {
                // Check if slot is already booked
                if (bookedSlots.includes(parseInt(slot.id))) {
                    return false;
                }

                // If selected date is today, check if time has passed
                if (selectedDate === today) {
                    const slotStartTime = slot.start_time.substring(0, 5); // HH:MM
                    const [slotHour, slotMinute] = slotStartTime.split(':').map(Number);
                    const slotTimeInMinutes = slotHour * 60 + slotMinute;

                    // Hide if slot time has passed
                    if (slotTimeInMinutes <= currentTimeInMinutes) {
                        return false;
                    }
                }

                return true;
            });

            renderTimeSlots(bookedSlots, availableSlots, selectedDate === today);
        }

        // Render time slots in dropdown
        function renderTimeSlots(bookedSlots = [], availableSlots = null, isToday = false) {
            const slotSelect = document.getElementById('timeSlotId');
            const currentSelection = slotSelect.value;

            // Clear current options except the first one
            slotSelect.innerHTML = '<option value="">Pilih jam main</option>';

            const slotsToRender = availableSlots || timeSlots;

            slotsToRender.forEach(slot => {
                const option = document.createElement('option');
                option.value = slot.id;
                option.textContent = `${slot.start_time.substring(0, 5)} - ${slot.end_time.substring(0, 5)}`;

                // Restore selection if still available
                if (slot.id == currentSelection) {
                    option.selected = true;
                }

                slotSelect.appendChild(option);
            });

            // Show message if no slots available
            if (slotsToRender.length === 0) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = isToday ? 'Tidak ada jam tersedia untuk hari ini' : 'Semua jam sudah dibooking';
                option.disabled = true;
                slotSelect.appendChild(option);
            }
        }

        // Update summary
        function updateSummary() {
            const dateInput = document.getElementById('bookingDate');
            const slotSelect = document.getElementById('timeSlotId');
            const summaryContent = document.getElementById('summaryContent');
            const totalPriceEl = document.getElementById('totalPrice');

            const date = dateInput.value;
            const slotId = slotSelect.value;

            if (!date || !slotId) {
                summaryContent.innerHTML = '<p class="text-muted text-center">Pilih tanggal dan jam untuk melihat ringkasan</p>';
                totalPriceEl.textContent = 'Rp 0';
                updatePaymentAmounts(0);
                return;
            }

            // Find slot
            const slot = timeSlots.find(s => s.id == slotId);
            if (!slot) return;

            // Check if weekend or tanggal merah (public holiday)
            const dateObj = new Date(date);
            const dayOfWeek = dateObj.getDay();
            const isWeekend = (dayOfWeek === 0 || dayOfWeek === 6) || currentDateIsRedDate;
            const dayType = isWeekend ? 'weekend' : 'weekday';

            // Get time slot key
            const startTime = slot.start_time.substring(0, 5);
            const endTime = slot.end_time.substring(0, 5);
            const timeSlotKey = startTime + '-' + endTime;

            // Get price
            let basePrice = prices[dayType] && prices[dayType][timeSlotKey] ? parseInt(prices[dayType][timeSlotKey]) : 400000;

            // Services are ordered via WA, not included in booking total
            const servicesTotal = 0;
            const total = basePrice + servicesTotal;

            // Update payment amounts
            updatePaymentAmounts(total);

            // Get selected payment type
            const selectedPaymentType = document.querySelector('input[name="payment_type"]:checked');
            const paymentType = selectedPaymentType ? selectedPaymentType.value : 'full';

            // Calculate display amount based on payment type
            let displayAmount = total;
            let paymentLabel = 'Total';

            if (dpAvailableForDate && paymentType === 'dp') {
                displayAmount = dpAmount;
                paymentLabel = 'Bayar Sekarang (DP)';
            }

            // Format date
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const formattedDate = dateObj.toLocaleDateString('id-ID', options);

            let paymentTypeHtml = '';
            if (dpAvailableForDate) {
                paymentTypeHtml = `
                    <div class="d-flex justify-between mb-1">
                        <span class="text-muted">Metode Bayar</span>
                        <span class="badge ${paymentType === 'dp' ? 'badge-warning' : 'badge-success'}">${paymentType === 'dp' ? 'DP Rp100.000' : 'Lunas'}</span>
                    </div>
                `;
            }

            summaryContent.innerHTML = `
                <div style="margin-bottom: var(--space-md); padding-bottom: var(--space-md); border-bottom: 1px solid var(--gray-100);">
                    <div class="d-flex justify-between mb-1">
                        <span class="text-muted">Tanggal</span>
                        <span style="font-weight: 600;">${formattedDate}</span>
                    </div>
                    <div class="d-flex justify-between mb-1">
                        <span class="text-muted">Jam</span>
                        <span style="font-weight: 600;">${startTime} - ${endTime}</span>
                    </div>
                    <div class="d-flex justify-between">
                        <span class="text-muted">Tipe Hari</span>
                        <span class="badge ${isWeekend ? 'badge-warning' : 'badge-primary'}">${currentDateIsRedDate ? 'Tanggal Merah' : (isWeekend ? 'Weekend' : 'Weekday')}</span>
                    </div>
                    ${paymentTypeHtml}
                </div>
                <div style="margin-bottom: var(--space-md);">
                    <div class="d-flex justify-between mb-1">
                        <span>Harga Sewa</span>
                        <span>Rp ${basePrice.toLocaleString('id-ID')}</span>
                    </div>
                    ${servicesTotal > 0 ? `<div class="d-flex justify-between mb-1"><span>Layanan Tambahan</span><span>Rp ${servicesTotal.toLocaleString('id-ID')}</span></div>` : ''}
                    <div class="d-flex justify-between" style="padding-top: 8px; border-top: 1px dashed var(--gray-200); margin-top: 8px;">
                        <span style="font-weight: 600;">Total</span>
                        <span style="font-weight: 600;">Rp ${total.toLocaleString('id-ID')}</span>
                    </div>
                </div>
            `;

            // Update total display based on payment type
            if (dpAvailableForDate && paymentType === 'dp') {
                totalPriceEl.innerHTML = `<span style="font-size: 0.875rem; color: var(--gray-500); display: block;">Bayar Sekarang</span>Rp ${displayAmount.toLocaleString('id-ID')}`;
            } else {
                totalPriceEl.textContent = 'Rp ' + displayAmount.toLocaleString('id-ID');
            }
        }

        // Update payment option amounts
        function updatePaymentAmounts(total) {
            const fullPaymentEl = document.getElementById('fullPaymentAmount');
            const dpAmountEl = document.getElementById('dpAmount');
            const remainingAmountEl = document.getElementById('remainingAmount');

            if (fullPaymentEl) {
                fullPaymentEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
            }

            if (dpAmountEl && remainingAmountEl) {
                const dpAmt = dpAmount;
                const remaining = total - dpAmt;
                dpAmountEl.textContent = 'Rp ' + dpAmount.toLocaleString('id-ID');
                remainingAmountEl.textContent = 'Rp ' + remaining.toLocaleString('id-ID');
            }
        }

        // Event listeners
        document.getElementById('bookingDate').addEventListener('change', async function() {
            const selectedDate = this.value;
            // Check promo status for selected date (affects DP availability)
            await checkPromoForDate(selectedDate);
            await updateAvailableTimeSlots();
            updateSummary();
        });
        document.getElementById('timeSlotId').addEventListener('change', updateSummary);
        document.querySelectorAll('input[name="services[]"]').forEach(el => {
            el.addEventListener('change', updateSummary);
        });

        // Payment type selection
        document.querySelectorAll('input[name="payment_type"]').forEach(el => {
            el.addEventListener('change', updateSummary);
        });

        // Initial update
        updatePaymentSectionVisibility();
        updateSummary();

        // If date is pre-selected, check promo for that date
        const initialDate = document.getElementById('bookingDate').value;
        if (initialDate) {
            checkPromoForDate(initialDate).then(() => {
                updateAvailableTimeSlots();
                updateSummary();
            });
        }
        
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

    <style>
        @media (max-width: 768px) {
            .d-grid {
                grid-template-columns: 1fr !important;
            }
        }

        /* Payment Option Styling */
        .payment-option {
            transition: all 0.2s ease;
        }

        .payment-option:hover {
            border-color: var(--primary-300) !important;
            background: var(--primary-50) !important;
        }

        .payment-option input:checked ~ div .payment-radio {
            border-color: var(--primary-500) !important;
        }

        .payment-option input:checked ~ div .payment-radio-inner {
            display: block !important;
        }

        .payment-option:has(input:checked) {
            border-color: var(--primary-500) !important;
            background: var(--primary-50) !important;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }
    </style>
</body>
</html>