<?php
require_once 'includes/config.php';

// Get site settings
$siteName = getSetting('site_name');
$siteTagline = getSetting('site_tagline');
$sitePhone = getSetting('site_phone');
$siteEmail = getSetting('site_email');
$siteAddress = getSetting('site_address');
$siteWhatsapp = getSetting('site_whatsapp');

// Get facilities
$db = getDB();
$facilitiesStmt = $db->query("SELECT * FROM facilities WHERE is_active = 1 ORDER BY sort_order");
$facilities = $facilitiesStmt->fetchAll();

// Get price settings
$weekdayPricesStmt = $db->query("SELECT * FROM price_settings WHERE day_type = 'weekday' AND is_active = 1 ORDER BY time_slot");
$weekdayPrices = $weekdayPricesStmt->fetchAll();

$weekendPricesStmt = $db->query("SELECT * FROM price_settings WHERE day_type = 'weekend' AND is_active = 1 ORDER BY time_slot");
$weekendPrices = $weekendPricesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($siteTagline); ?> - Booking lapangan mini soccer online dengan mudah">
    <title><?php echo htmlspecialchars($siteName); ?> - <?php echo htmlspecialchars($siteTagline); ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
        
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i data-lucide="map-pin" width="16" height="16"></i>
                    Gerung, Lombok Barat
                </div>
                
                <h1 class="hero-title">
                    Lapangan Mini Soccer<br>
                    <span>Terbaik di Gerung</span>
                </h1>
                
                <p class="hero-description">
                    Nikmati pengalaman bermain mini soccer di lapangan berstandar dengan fasilitas lengkap. 
                    Booking online mudah, harga terjangkau.
                </p>
                
                <div class="hero-buttons">
                    <a href="booking.php" class="btn btn-primary btn-lg">
                        <i data-lucide="calendar-check" width="20" height="20"></i>
                        Booking Sekarang
                    </a>
                    <a href="jadwal.php" class="btn btn-lg" style="background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.3); color: white;">
                        <i data-lucide="calendar" width="20" height="20"></i>
                        Lihat Jadwal
                    </a>
                </div>
                
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-value">500+</div>
                        <div class="stat-label">Booking Bulanan</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">4.9</div>
                        <div class="stat-label">Rating Pengguna</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">24/7</div>
                        <div class="stat-label">Layanan Online</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section bg-white">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Fasilitas Kami</h2>
                <p class="section-subtitle">
                    Dilengkapi dengan berbagai fasilitas modern untuk kenyamanan bermain Anda
                </p>
            </div>
            
            <div class="d-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-lg);">
                <?php 
                $iconMap = [
                    'field' => 'layout-grid',
                    'door' => 'door-open',
                    'car' => 'car',
                    'coffee' => 'coffee',
                    'droplet' => 'droplets',
                    'users' => 'users',
                    'wifi' => 'wifi',
                    'sun' => 'sun'
                ];
                foreach ($facilities as $facility): 
                    $icon = $iconMap[$facility['icon']] ?? 'check-circle';
                ?>
                <div class="card feature-card">
                    <div class="feature-icon">
                        <i data-lucide="<?php echo $icon; ?>" width="28" height="28"></i>
                    </div>
                    <h4 class="feature-title"><?php echo htmlspecialchars($facility['name']); ?></h4>
                    <p class="feature-description"><?php echo htmlspecialchars($facility['description']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- How to Book Section -->
    <section class="section" style="background: var(--gray-50);">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Cara Booking</h2>
                <p class="section-subtitle">Booking lapangan dalam 3 langkah mudah</p>
            </div>
            
            <div class="d-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-xl);">
                <div class="text-center">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary-500), var(--primary-600)); border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg); color: white; font-family: var(--font-display); font-size: 2rem; font-weight: 800;">1</div>
                    <h4 class="mb-2">Pilih Tanggal & Waktu</h4>
                    <p class="text-muted mb-0">Lihat jadwal yang tersedia dan pilih slot waktu yang Anda inginkan</p>
                </div>
                
                <div class="text-center">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary-500), var(--primary-600)); border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg); color: white; font-family: var(--font-display); font-size: 2rem; font-weight: 800;">2</div>
                    <h4 class="mb-2">Isi Data & Konfirmasi</h4>
                    <p class="text-muted mb-0">Lengkapi data pemesanan dan pilih layanan tambahan jika diperlukan</p>
                </div>
                
                <div class="text-center">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary-500), var(--primary-600)); border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg); color: white; font-family: var(--font-display); font-size: 2rem; font-weight: 800;">3</div>
                    <h4 class="mb-2">Bayar & Main</h4>
                    <p class="text-muted mb-0">Lakukan pembayaran dan datang sesuai jadwal untuk bermain</p>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="booking.php" class="btn btn-primary btn-lg">
                    <i data-lucide="arrow-right" width="20" height="20"></i>
                    Mulai Booking
                </a>
            </div>
        </div>
    </section>

    <!-- Price Preview Section -->
    <section class="section bg-white">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Daftar Harga</h2>
                <p class="section-subtitle">Harga terjangkau dengan fasilitas terbaik</p>
            </div>
            
            <div class="d-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: var(--space-xl);">
                <!-- Weekday Prices -->
                <div class="card">
                    <div class="card-header" style="background: var(--primary-600); color: white;">
                        <h4 class="mb-0" style="color: white;">
                            <i data-lucide="briefcase" width="20" height="20" style="vertical-align: middle; margin-right: 8px;"></i>
                            Weekday (Senin - Jumat)
                        </h4>
                    </div>
                    <div class="card-body p-0">
                        <table class="price-table">
                            <thead>
                                <tr>
                                    <th>Jam</th>
                                    <th style="text-align: right;">Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($weekdayPrices as $price): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($price['time_slot']); ?></td>
                                    <td style="text-align: right;" class="price-amount"><?php echo formatRupiah($price['price']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Weekend Prices -->
                <div class="card">
                    <div class="card-header" style="background: var(--accent-gold); color: var(--gray-900);">
                        <h4 class="mb-0" style="color: var(--gray-900);">
                            <i data-lucide="sun" width="20" height="20" style="vertical-align: middle; margin-right: 8px;"></i>
                            Weekend / Libur
                        </h4>
                    </div>
                    <div class="card-body p-0">
                        <table class="price-table">
                            <thead>
                                <tr>
                                    <th>Jam</th>
                                    <th style="text-align: right;">Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($weekendPrices as $price): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($price['time_slot']); ?></td>
                                    <td style="text-align: right;" class="price-amount"><?php echo formatRupiah($price['price']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info mt-4">
                <i data-lucide="info" width="20" height="20"></i>
                <div>
                    <strong>Informasi:</strong> Harga sudah termasuk pajak 10%. Tarif berbeda antara hari biasa dengan weekend/hari libur.
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="harga.php" class="btn btn-outline">
                    <i data-lucide="list" width="18" height="18"></i>
                    Lihat Detail Harga
                </a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section" style="background: linear-gradient(135deg, var(--primary-600), var(--primary-800)); color: white;">
        <div class="container text-center">
            <h2 style="color: white; margin-bottom: var(--space-md);">Siap Bermain?</h2>
            <p style="opacity: 0.9; margin-bottom: var(--space-xl); max-width: 500px; margin-left: auto; margin-right: auto;">
                Booking sekarang dan nikmati pengalaman bermain mini soccer terbaik di Gelora Gerung
            </p>
            <div class="d-flex justify-center gap-3 flex-wrap">
                <a href="booking.php" class="btn btn-lg" style="background: white; color: var(--primary-700);">
                    <i data-lucide="calendar-plus" width="20" height="20"></i>
                    Booking Online
                </a>
                <a href="https://wa.me/<?php echo $siteWhatsapp; ?>" target="_blank" class="btn btn-lg" style="background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.3);">
                    <i data-lucide="message-circle" width="20" height="20"></i>
                    Hubungi via WhatsApp
                </a>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    
    <!-- WhatsApp Float -->
    <div class="whatsapp-float">
        <a href="https://wa.me/<?php echo $siteWhatsapp; ?>" target="_blank" class="whatsapp-btn" title="Chat via WhatsApp">
            <i data-lucide="message-circle" width="28" height="28"></i>
        </a>
    </div>

    <script>
        // Initialize Lucide icons
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
        
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const mobileNav = document.getElementById('mobileNav');
        
        if (menuToggle && mobileNav) {
            menuToggle.addEventListener('click', function() {
                mobileNav.classList.toggle('active');
                this.classList.toggle('active');
            });
        }
    </script>
</body>
</html>
