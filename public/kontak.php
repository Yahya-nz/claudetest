<?php
require_once 'includes/config.php';

$sitePhone = getSetting('site_phone');
$siteEmail = getSetting('site_email');
$siteAddress = getSetting('site_address');
$siteWhatsapp = getSetting('site_whatsapp');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak - <?php echo getSetting('site_name'); ?></title>
    
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
            <h1 style="color: white; margin-bottom: var(--space-md);">Hubungi Kami</h1>
            <p style="opacity: 0.9; max-width: 500px; margin: 0 auto;">
                Ada pertanyaan? Jangan ragu untuk menghubungi kami
            </p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="section">
        <div class="container">
            <div class="d-grid" style="grid-template-columns: 1fr 1fr; gap: var(--space-2xl);">
                <!-- Contact Info -->
                <div>
                    <h2 class="mb-3">Informasi Kontak</h2>
                    <p class="text-muted mb-4">
                        Silakan hubungi kami melalui salah satu channel di bawah ini. Tim kami siap membantu Anda.
                    </p>
                    
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-center gap-3">
                                <div style="width: 56px; height: 56px; background: var(--primary-50); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i data-lucide="map-pin" width="24" height="24" style="color: var(--primary-600);"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Alamat</h5>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($siteAddress); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-center gap-3">
                                <div style="width: 56px; height: 56px; background: var(--primary-50); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i data-lucide="phone" width="24" height="24" style="color: var(--primary-600);"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Telepon</h5>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($sitePhone); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-center gap-3">
                                <div style="width: 56px; height: 56px; background: var(--primary-50); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i data-lucide="mail" width="24" height="24" style="color: var(--primary-600);"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Email</h5>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($siteEmail); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card" style="background: linear-gradient(135deg, #25d366, #128c7e); color: white;">
                        <div class="card-body">
                            <div class="d-flex align-center justify-between">
                                <div class="d-flex align-center gap-3">
                                    <div style="width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i data-lucide="message-circle" width="24" height="24"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1" style="color: white;">WhatsApp</h5>
                                        <p style="opacity: 0.9; margin: 0;">Chat langsung dengan admin</p>
                                    </div>
                                </div>
                                <a href="https://wa.me/<?php echo $siteWhatsapp; ?>" target="_blank" class="btn" style="background: white; color: #25d366;">
                                    Chat Sekarang
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Operating Hours -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h4 class="mb-0">
                                <i data-lucide="clock" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                Jam Operasional
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-between mb-2">
                                <span>Senin - Jumat</span>
                                <span style="font-weight: 600;">06:00 - 24:00</span>
                            </div>
                            <div class="d-flex justify-between mb-2">
                                <span>Sabtu - Minggu</span>
                                <span style="font-weight: 600;">06:00 - 24:00</span>
                            </div>
                            <div class="d-flex justify-between">
                                <span>Hari Libur</span>
                                <span style="font-weight: 600;">06:00 - 24:00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">
                            <i data-lucide="send" width="24" height="24" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                            Kirim Pesan
                        </h3>
                    </div>
                    <div class="card-body">
                        <form id="contactForm">
                            <div class="form-group">
                                <label class="form-label">Nama Lengkap *</label>
                                <input type="text" name="name" class="form-input" required placeholder="Masukkan nama Anda">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-input" required placeholder="email@example.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">No. Telepon</label>
                                <input type="tel" name="phone" class="form-input" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Subjek *</label>
                                <select name="subject" class="form-select" required>
                                    <option value="">Pilih subjek</option>
                                    <option value="booking">Pertanyaan Booking</option>
                                    <option value="harga">Informasi Harga</option>
                                    <option value="member">Pendaftaran Member</option>
                                    <option value="kerjasama">Kerjasama</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Pesan *</label>
                                <textarea name="message" class="form-textarea" required placeholder="Tulis pesan Anda di sini..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i data-lucide="send" width="18" height="18"></i>
                                Kirim Pesan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="section" style="background: var(--gray-50);">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Pertanyaan Umum</h2>
                <p class="section-subtitle">Jawaban untuk pertanyaan yang sering diajukan</p>
            </div>
            
            <div style="max-width: 800px; margin: 0 auto;">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="mb-2">
                            <i data-lucide="help-circle" width="18" height="18" style="color: var(--primary-600); margin-right: 8px;"></i>
                            Bagaimana cara melakukan booking?
                        </h5>
                        <p class="text-muted mb-0">
                            Anda dapat melakukan booking melalui website kami dengan memilih tanggal dan jam yang tersedia, 
                            mengisi data diri, dan melakukan pembayaran. Atau bisa juga menghubungi kami via WhatsApp.
                        </p>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="mb-2">
                            <i data-lucide="help-circle" width="18" height="18" style="color: var(--primary-600); margin-right: 8px;"></i>
                            Berapa lama waktu booking?
                        </h5>
                        <p class="text-muted mb-0">
                            Setiap sesi booking adalah 2 jam. Jika Anda membutuhkan waktu lebih lama, 
                            silakan booking untuk beberapa sesi sekaligus.
                        </p>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="mb-2">
                            <i data-lucide="help-circle" width="18" height="18" style="color: var(--primary-600); margin-right: 8px;"></i>
                            Apakah bisa membatalkan booking?
                        </h5>
                        <p class="text-muted mb-0">
                            Pembatalan dapat dilakukan maksimal 24 jam sebelum jadwal main. 
                            Silakan hubungi admin untuk proses pembatalan.
                        </p>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="mb-2">
                            <i data-lucide="help-circle" width="18" height="18" style="color: var(--primary-600); margin-right: 8px;"></i>
                            Apa keuntungan menjadi member?
                        </h5>
                        <p class="text-muted mb-0">
                            Member mendapatkan diskon 10% untuk setiap booking, prioritas booking jam prime time, 
                            dan akses ke promo-promo khusus member.
                        </p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-2">
                            <i data-lucide="help-circle" width="18" height="18" style="color: var(--primary-600); margin-right: 8px;"></i>
                            Metode pembayaran apa saja yang tersedia?
                        </h5>
                        <p class="text-muted mb-0">
                            Kami menerima pembayaran via transfer bank (BCA, Mandiri, BRI, BNI), 
                            e-wallet (GoPay, OVO, Dana), dan pembayaran tunai di tempat.
                        </p>
                    </div>
                </div>
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
        
        // Contact form
        document.getElementById('contactForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader" width="18" height="18" style="animation: spin 1s linear infinite;"></i> Mengirim...';

            const formData = new FormData(this);

            try {
                const response = await fetch('contact-processor.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success mb-4';
                    alertDiv.innerHTML = `
                        <i data-lucide="check-circle" width="20" height="20"></i>
                        <span>${result.message}</span>
                    `;
                    this.parentElement.insertBefore(alertDiv, this);

                    // Reset form
                    this.reset();

                    // Initialize icons for the new alert
                    lucide.createIcons();

                    // Remove alert after 5 seconds
                    setTimeout(() => alertDiv.remove(), 5000);
                } else {
                    // Show error message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-error mb-4';
                    alertDiv.innerHTML = `
                        <i data-lucide="alert-circle" width="20" height="20"></i>
                        <div>
                            <strong>${result.message}</strong>
                            ${result.errors ? '<ul style="margin: 8px 0 0 20px;">' + result.errors.map(err => '<li>' + err + '</li>').join('') + '</ul>' : ''}
                        </div>
                    `;
                    this.parentElement.insertBefore(alertDiv, this);
                    lucide.createIcons();
                    setTimeout(() => alertDiv.remove(), 7000);
                }
            } catch (error) {
                console.error('Error:', error);
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-error mb-4';
                alertDiv.innerHTML = `
                    <i data-lucide="alert-circle" width="20" height="20"></i>
                    <span>Terjadi kesalahan. Silakan coba lagi atau hubungi kami via WhatsApp.</span>
                `;
                this.parentElement.insertBefore(alertDiv, this);
                lucide.createIcons();
                setTimeout(() => alertDiv.remove(), 5000);
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                lucide.createIcons();
            }
        });
    </script>

    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .d-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</body>
</html>
