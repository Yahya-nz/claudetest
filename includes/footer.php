<?php
$siteName = getSetting('site_name');
$sitePhone = getSetting('site_phone');
$siteEmail = getSetting('site_email');
$siteAddress = getSetting('site_address');
$siteWhatsapp = getSetting('site_whatsapp');
?>
<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Brand -->
            <div class="footer-brand">
                <div class="footer-logo">
                    <div class="footer-logo-icon">
                        <i data-lucide="circle-dot" width="20" height="20"></i>
                    </div>
                    <span class="footer-logo-text">GELORA GERUNG</span>
                </div>
                <p class="footer-description">
                    Lapangan mini soccer terbaik di Gerung dengan fasilitas lengkap dan pelayanan profesional.
                </p>
                <div class="footer-social">
                    <a href="#" class="social-link" title="Facebook">
                        <i data-lucide="facebook" width="18" height="18"></i>
                    </a>
                    <a href="#" class="social-link" title="Instagram">
                        <i data-lucide="instagram" width="18" height="18"></i>
                    </a>
                    <a href="https://wa.me/<?php echo $siteWhatsapp; ?>" class="social-link" title="WhatsApp">
                        <i data-lucide="message-circle" width="18" height="18"></i>
                    </a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h5 class="footer-title">Menu</h5>
                <ul class="footer-links">
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="jadwal.php">Jadwal</a></li>
                    <li><a href="harga.php">Harga</a></li>
                    <li><a href="booking.php">Booking</a></li>
                    <li><a href="tentang.php">Tentang Kami</a></li>
                </ul>
            </div>
            
            <!-- Services -->
            <div>
                <h5 class="footer-title">Layanan</h5>
                <ul class="footer-links">
                    <li><a href="booking.php">Sewa Lapangan</a></li>
                    <li><a href="harga.php#tambahan">Layanan Tambahan</a></li>
                    <li><a href="booking.php#member">Member</a></li>
                    <li><a href="kontak.php">FAQ</a></li>
                </ul>
            </div>
            
            <!-- Contact -->
            <div>
                <h5 class="footer-title">Kontak</h5>
                <div class="footer-contact-item">
                    <div class="footer-contact-icon">
                        <i data-lucide="map-pin" width="18" height="18"></i>
                    </div>
                    <div>
                        <p class="mb-0"><?php echo htmlspecialchars($siteAddress); ?></p>
                    </div>
                </div>
                <div class="footer-contact-item">
                    <div class="footer-contact-icon">
                        <i data-lucide="phone" width="18" height="18"></i>
                    </div>
                    <div>
                        <p class="mb-0"><?php echo htmlspecialchars($sitePhone); ?></p>
                    </div>
                </div>
                <div class="footer-contact-item">
                    <div class="footer-contact-icon">
                        <i data-lucide="mail" width="18" height="18"></i>
                    </div>
                    <div>
                        <p class="mb-0"><?php echo htmlspecialchars($siteEmail); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p class="footer-copyright">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?>. All rights reserved.
            </p>
            <div class="footer-links" style="display: flex; gap: var(--space-lg); list-style: none;">
                <a href="#">Syarat & Ketentuan</a>
                <a href="#">Kebijakan Privasi</a>
            </div>
        </div>
    </div>
</footer>
