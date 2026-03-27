<?php
require_once 'includes/config.php';

$db = getDB();

// Get price settings
$weekdayPricesStmt = $db->query("SELECT * FROM price_settings WHERE day_type = 'weekday' AND is_active = 1 ORDER BY time_slot");
$weekdayPrices = $weekdayPricesStmt->fetchAll();

$weekendPricesStmt = $db->query("SELECT * FROM price_settings WHERE day_type = 'weekend' AND is_active = 1 ORDER BY time_slot");
$weekendPrices = $weekendPricesStmt->fetchAll();

// Get additional services
$servicesStmt = $db->query("SELECT * FROM additional_services WHERE is_active = 1 ORDER BY price");
$services = $servicesStmt->fetchAll();

$taxPercent = getSetting('tax_percent');
$photographerFee = getSetting('photographer_fee');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Harga - <?php echo getSetting('site_name'); ?></title>
    
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
            <h1 style="color: white; margin-bottom: var(--space-md);">Daftar Harga</h1>
            <p style="opacity: 0.9; max-width: 500px; margin: 0 auto;">
                Harga sewa lapangan mini soccer dan layanan tambahan
            </p>
        </div>
    </section>

    <!-- Price Section -->
    <section class="section">
        <div class="container">
            <div class="d-grid" style="grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: var(--space-xl);">
                <!-- Weekday Prices -->
                <div class="card">
                    <div class="card-header" style="background: linear-gradient(135deg, var(--primary-500), var(--primary-600)); color: white;">
                        <div class="d-flex align-center gap-2">
                            <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                                <i data-lucide="briefcase" width="24" height="24"></i>
                            </div>
                            <div>
                                <h3 style="color: white; margin: 0; font-size: 1.25rem;">Weekday</h3>
                                <p style="margin: 0; opacity: 0.9; font-size: 0.875rem;">Senin - Jumat</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="price-table">
                            <thead>
                                <tr>
                                    <th>Jam Main</th>
                                    <th style="text-align: right;">Harga per 2 Jam</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($weekdayPrices as $price): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-center gap-2">
                                            <i data-lucide="clock" width="16" height="16" style="color: var(--gray-400);"></i>
                                            <?php echo htmlspecialchars($price['time_slot']); ?>
                                        </div>
                                    </td>
                                    <td style="text-align: right;">
                                        <span class="price-amount" style="font-size: 1.125rem;"><?php echo formatRupiah($price['price']); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Weekend Prices -->
                <div class="card">
                    <div class="card-header" style="background: linear-gradient(135deg, var(--accent-gold), #d97706); color: white;">
                        <div class="d-flex align-center gap-2">
                            <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                                <i data-lucide="sun" width="24" height="24"></i>
                            </div>
                            <div>
                                <h3 style="color: white; margin: 0; font-size: 1.25rem;">Weekend / Hari Libur</h3>
                                <p style="margin: 0; opacity: 0.9; font-size: 0.875rem;">Sabtu, Minggu & Hari Libur Nasional</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="price-table">
                            <thead>
                                <tr>
                                    <th>Jam Main</th>
                                    <th style="text-align: right;">Harga per 2 Jam</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($weekendPrices as $price): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-center gap-2">
                                            <i data-lucide="clock" width="16" height="16" style="color: var(--gray-400);"></i>
                                            <?php echo htmlspecialchars($price['time_slot']); ?>
                                        </div>
                                    </td>
                                    <td style="text-align: right;">
                                        <span class="price-amount" style="font-size: 1.125rem;"><?php echo formatRupiah($price['price']); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Info Notice -->
            <div class="alert alert-info mt-4">
                <i data-lucide="info" width="20" height="20"></i>
                <div>
                    <strong>Informasi Penting:</strong>
                    <ul style="margin: var(--space-sm) 0 0; padding-left: var(--space-lg);">
                        <li>Harga sudah termasuk pajak <?php echo $taxPercent; ?>%</li>
                        <li>Tarif berbeda antara hari biasa dengan weekend/hari libur nasional</li>
                        <li>Minimal DP Rp 100.000 untuk konfirmasi booking</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Additional Services Section -->
    <section class="section" style="background: var(--gray-50);" id="tambahan">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Layanan Tambahan</h2>
                <p class="section-subtitle">
                    Tambahkan layanan berikut untuk pengalaman bermain yang lebih lengkap
                </p>
            </div>

            <div class="d-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-lg);">
                <?php foreach ($services as $service): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-between align-center mb-3">
                            <h4 class="mb-0"><?php echo htmlspecialchars($service['name']); ?></h4>
                            <span class="badge badge-primary"><?php echo formatRupiah($service['price']); ?></span>
                        </div>
                        <?php if ($service['description']): ?>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($service['description']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Photographer Notice -->
            <div class="alert alert-warning mt-4">
                <i data-lucide="camera" width="20" height="20"></i>
                <div>
                    <strong>Perhatian:</strong> Jika membawa fotografer sendiri, maka akan dikenakan biaya tambahan sebesar <?php echo formatRupiah($photographerFee); ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Member Section -->
    <section class="section" id="member">
        <div class="container">
            <div class="card" style="background: linear-gradient(135deg, var(--primary-600), var(--primary-800)); color: white; overflow: hidden;">
                <div class="card-body" style="padding: var(--space-2xl);">
                    <div class="d-grid" style="grid-template-columns: 1fr 1fr; gap: var(--space-2xl); align-items: center;">
                        <div>
                            <span class="badge" style="background: rgba(255,255,255,0.2); color: white; margin-bottom: var(--space-md);">
                                <i data-lucide="star" width="14" height="14" style="margin-right: 4px;"></i>
                                Member Exclusive
                            </span>
                            <h2 style="color: white; margin-bottom: var(--space-md);">Jadi Member & Dapatkan Diskon 10%</h2>
                            <p style="opacity: 0.9; margin-bottom: var(--space-lg);">
                                Daftar sebagai member untuk mendapatkan harga spesial dan berbagai keuntungan lainnya setiap kali booking lapangan.
                            </p>
                            <ul style="list-style: none; padding: 0; margin-bottom: var(--space-lg);">
                                <li style="display: flex; align-items: center; gap: var(--space-sm); margin-bottom: var(--space-sm);">
                                    <i data-lucide="check-circle" width="20" height="20" style="color: var(--accent-amber);"></i>
                                    Diskon 10% setiap booking
                                </li>
                                <li style="display: flex; align-items: center; gap: var(--space-sm); margin-bottom: var(--space-sm);">
                                    <i data-lucide="check-circle" width="20" height="20" style="color: var(--accent-amber);"></i>
                                    Prioritas booking jam prime time
                                </li>
                                <li style="display: flex; align-items: center; gap: var(--space-sm); margin-bottom: var(--space-sm);">
                                    <i data-lucide="check-circle" width="20" height="20" style="color: var(--accent-amber);"></i>
                                    Akses ke promo khusus member
                                </li>
                            </ul>
                            <a href="kontak.php" class="btn btn-lg" style="background: white; color: var(--primary-700);">
                                <i data-lucide="user-plus" width="20" height="20"></i>
                                Daftar Member
                            </a>
                        </div>
                        <div class="text-center" style="display: none;">
                            <!-- Decorative element can go here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section" style="background: var(--gray-50);">
        <div class="container text-center">
            <h2 class="mb-2">Siap Bermain?</h2>
            <p class="text-muted mb-4" style="max-width: 500px; margin-left: auto; margin-right: auto;">
                Booking sekarang dan nikmati pengalaman bermain mini soccer terbaik di Gelora Gerung
            </p>
            <a href="booking.php" class="btn btn-primary btn-lg">
                <i data-lucide="calendar-plus" width="20" height="20"></i>
                Booking Sekarang
            </a>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script>
        lucide.createIcons();
        
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
