<?php
require_once 'includes/config.php';

$db = getDB();
$facilitiesStmt = $db->query("SELECT * FROM facilities WHERE is_active = 1 ORDER BY sort_order");
$facilities = $facilitiesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - <?php echo getSetting('site_name'); ?></title>
    
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
            <h1 style="color: white; margin-bottom: var(--space-md);">Tentang Kami</h1>
            <p style="opacity: 0.9; max-width: 500px; margin: 0 auto;">
                Mengenal lebih dekat Minisoccer Gelora Gerung
            </p>
        </div>
    </section>

    <!-- About Section -->
    <section class="section">
        <div class="container">
            <div class="d-grid" style="grid-template-columns: 1fr 1fr; gap: var(--space-2xl); align-items: center;">
                <div>
                    <span class="badge badge-primary mb-3">Sejak 2024</span>
                    <h2 class="mb-3">Lapangan Mini Soccer Terbaik di Gerung</h2>
                    <p class="text-muted mb-3">
                        Minisoccer Gelora Gerung hadir sebagai solusi bagi para pecinta olahraga mini soccer di Gerung dan sekitarnya. 
                        Kami menyediakan lapangan mini soccer dengan standar kualitas terbaik dan fasilitas lengkap untuk 
                        memberikan pengalaman bermain yang tak terlupakan.
                    </p>
                    <p class="text-muted mb-4">
                        Dengan komitmen untuk terus meningkatkan kualitas layanan, kami berusaha menjadi pilihan utama 
                        bagi masyarakat yang ingin berolahraga dengan nyaman dan menyenangkan.
                    </p>
                    <div class="d-flex gap-4">
                        <div>
                            <div style="font-size: 2rem; font-weight: 800; color: var(--primary-600);">500+</div>
                            <div class="text-muted">Booking / Bulan</div>
                        </div>
                        <div>
                            <div style="font-size: 2rem; font-weight: 800; color: var(--primary-600);">4.9</div>
                            <div class="text-muted">Rating</div>
                        </div>
                        <div>
                            <div style="font-size: 2rem; font-weight: 800; color: var(--primary-600);">100%</div>
                            <div class="text-muted">Kepuasan</div>
                        </div>
                    </div>
                </div>
                <div style="background: linear-gradient(135deg, var(--primary-100), var(--primary-50)); border-radius: var(--radius-2xl); padding: var(--space-2xl); text-align: center;">
                    <div style="width: 150px; height: 150px; background: linear-gradient(135deg, var(--primary-500), var(--primary-600)); border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg);">
                        <i data-lucide="trophy" width="64" height="64" style="color: white;"></i>
                    </div>
                    <h3 class="mb-2">Komitmen Kami</h3>
                    <p class="text-muted mb-0">Memberikan pengalaman bermain mini soccer terbaik dengan fasilitas berkualitas dan pelayanan prima</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Vision Mission -->
    <section class="section" style="background: var(--gray-50);">
        <div class="container">
            <div class="d-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: var(--space-xl);">
                <div class="card">
                    <div class="card-body" style="padding: var(--space-2xl);">
                        <div style="width: 64px; height: 64px; background: linear-gradient(135deg, var(--primary-100), var(--primary-50)); border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; margin-bottom: var(--space-lg);">
                            <i data-lucide="eye" width="28" height="28" style="color: var(--primary-600);"></i>
                        </div>
                        <h3 class="mb-3">Visi</h3>
                        <p class="text-muted mb-0">
                            Menjadi penyedia fasilitas olahraga mini soccer terbaik dan terpercaya di Lombok Barat, 
                            yang berkontribusi dalam pengembangan olahraga dan gaya hidup sehat masyarakat.
                        </p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body" style="padding: var(--space-2xl);">
                        <div style="width: 64px; height: 64px; background: linear-gradient(135deg, var(--primary-100), var(--primary-50)); border-radius: var(--radius-xl); display: flex; align-items: center; justify-content: center; margin-bottom: var(--space-lg);">
                            <i data-lucide="target" width="28" height="28" style="color: var(--primary-600);"></i>
                        </div>
                        <h3 class="mb-3">Misi</h3>
                        <ul style="padding-left: var(--space-lg); color: var(--gray-600);">
                            <li style="margin-bottom: var(--space-sm);">Menyediakan lapangan dengan standar kualitas terbaik</li>
                            <li style="margin-bottom: var(--space-sm);">Memberikan pelayanan yang ramah dan profesional</li>
                            <li style="margin-bottom: var(--space-sm);">Menawarkan harga yang kompetitif dan terjangkau</li>
                            <li>Menciptakan komunitas olahraga yang aktif dan positif</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Facilities -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Fasilitas Kami</h2>
                <p class="section-subtitle">
                    Berbagai fasilitas modern untuk kenyamanan bermain Anda
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

    <!-- Why Choose Us -->
    <section class="section" style="background: linear-gradient(135deg, var(--primary-600), var(--primary-800)); color: white;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title" style="color: white;">Mengapa Memilih Kami?</h2>
                <p class="section-subtitle" style="color: rgba(255,255,255,0.8);">
                    Berbagai alasan mengapa Gelora Gerung menjadi pilihan tepat
                </p>
            </div>
            
            <div class="d-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--space-xl);">
                <div class="text-center">
                    <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg);">
                        <i data-lucide="shield-check" width="36" height="36"></i>
                    </div>
                    <h4 style="color: white;">Kualitas Terjamin</h4>
                    <p style="opacity: 0.8; margin-bottom: 0;">Lapangan dengan rumput sintetis berkualitas tinggi dan terawat dengan baik</p>
                </div>
                
                <div class="text-center">
                    <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg);">
                        <i data-lucide="wallet" width="36" height="36"></i>
                    </div>
                    <h4 style="color: white;">Harga Terjangkau</h4>
                    <p style="opacity: 0.8; margin-bottom: 0;">Harga kompetitif dengan berbagai pilihan paket sesuai kebutuhan</p>
                </div>
                
                <div class="text-center">
                    <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg);">
                        <i data-lucide="clock" width="36" height="36"></i>
                    </div>
                    <h4 style="color: white;">Booking Mudah</h4>
                    <p style="opacity: 0.8; margin-bottom: 0;">Sistem booking online 24/7 yang cepat dan mudah digunakan</p>
                </div>
                
                <div class="text-center">
                    <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg);">
                        <i data-lucide="heart" width="36" height="36"></i>
                    </div>
                    <h4 style="color: white;">Pelayanan Prima</h4>
                    <p style="opacity: 0.8; margin-bottom: 0;">Tim yang ramah dan siap membantu kebutuhan Anda</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section">
        <div class="container text-center">
            <h2 class="mb-2">Tertarik Bermain di Gelora Gerung?</h2>
            <p class="text-muted mb-4" style="max-width: 500px; margin-left: auto; margin-right: auto;">
                Booking sekarang dan rasakan sendiri pengalaman bermain di lapangan terbaik
            </p>
            <div class="d-flex justify-center gap-3 flex-wrap">
                <a href="booking.php" class="btn btn-primary btn-lg">
                    <i data-lucide="calendar-plus" width="20" height="20"></i>
                    Booking Sekarang
                </a>
                <a href="kontak.php" class="btn btn-outline btn-lg">
                    <i data-lucide="message-circle" width="20" height="20"></i>
                    Hubungi Kami
                </a>
            </div>
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

    <style>
        @media (max-width: 768px) {
            .d-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</body>
</html>
