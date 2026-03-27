<?php
require_once 'includes/config.php';

$db = getDB();

// Get operation start date
$operationStartDate = getSetting('operation_start_date') ?? '2026-02-02';

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
            
            // Calculate services total
            $servicesTotal = 0;
            
            // Generate booking code
            $bookingCode = generateBookingCode();
            
            // Insert booking
            $insertStmt = $db->prepare("
                INSERT INTO bookings (booking_code, customer_name, customer_phone, customer_email, team_name, booking_date, time_slot_id, booking_type, base_price, total_price, status, payment_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid')
            ");
            
            $totalPrice = $basePrice + $servicesTotal;
            
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
                $totalPrice
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
            
            $message = "Booking berhasil! Kode booking Anda: <strong>$bookingCode</strong>. Silakan lakukan pembayaran DP minimal Rp 100.000 dan kirim bukti transfer.";
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
                                Promo spesial diskon <strong><?php echo $promo['discount_percent']; ?>%</strong> akan dimulai pada
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

                            <!-- Additional Services -->
                            <div class="mb-4">
                                <h4 class="mb-3" style="color: var(--primary-700);">
                                    <i data-lucide="plus-circle" width="20" height="20" style="vertical-align: middle; margin-right: 8px;"></i>
                                    Layanan Tambahan
                                </h4>
                                <div class="d-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--space-md);">
                                    <?php foreach ($services as $service): ?>
                                    <label class="form-checkbox" style="padding: var(--space-md); background: var(--gray-50); border-radius: var(--radius-lg); cursor: pointer;">
                                        <input type="checkbox" name="services[]" value="<?php echo $service['id']; ?>" data-price="<?php echo $service['price']; ?>">
                                        <div>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($service['name']); ?></div>
                                            <div style="color: var(--primary-600); font-weight: 700;"><?php echo formatRupiah($service['price']); ?></div>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
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
                return;
            }

            // Find slot
            const slot = timeSlots.find(s => s.id == slotId);
            if (!slot) return;

            // Check if weekend
            const dateObj = new Date(date);
            const dayOfWeek = dateObj.getDay();
            const isWeekend = (dayOfWeek === 0 || dayOfWeek === 6);
            const dayType = isWeekend ? 'weekend' : 'weekday';

            // Get time slot key
            const startTime = slot.start_time.substring(0, 5);
            const endTime = slot.end_time.substring(0, 5);
            const timeSlotKey = startTime + '-' + endTime;

            // Get price
            let basePrice = prices[dayType] && prices[dayType][timeSlotKey] ? parseInt(prices[dayType][timeSlotKey]) : 400000;

            // Calculate services
            let servicesTotal = 0;
            const serviceCheckboxes = document.querySelectorAll('input[name="services[]"]:checked');
            serviceCheckboxes.forEach(cb => {
                servicesTotal += parseInt(cb.dataset.price);
            });

            const total = basePrice + servicesTotal;

            // Format date
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const formattedDate = dateObj.toLocaleDateString('id-ID', options);

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
                        <span class="badge ${isWeekend ? 'badge-warning' : 'badge-primary'}">${isWeekend ? 'Weekend' : 'Weekday'}</span>
                    </div>
                </div>
                <div style="margin-bottom: var(--space-md);">
                    <div class="d-flex justify-between mb-1">
                        <span>Harga Sewa</span>
                        <span>Rp ${basePrice.toLocaleString('id-ID')}</span>
                    </div>
                    ${servicesTotal > 0 ? `<div class="d-flex justify-between mb-1"><span>Layanan Tambahan</span><span>Rp ${servicesTotal.toLocaleString('id-ID')}</span></div>` : ''}
                </div>
            `;

            totalPriceEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
        }

        // Event listeners
        document.getElementById('bookingDate').addEventListener('change', async function() {
            await updateAvailableTimeSlots();
            updateSummary();
        });
        document.getElementById('timeSlotId').addEventListener('change', updateSummary);
        document.querySelectorAll('input[name="services[]"]').forEach(el => {
            el.addEventListener('change', updateSummary);
        });

        // Initial update
        updateSummary();
        
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
    </style>
</body>
</html>