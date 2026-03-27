<?php
$siteName = getSetting('site_name');
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!-- Header -->
<header class="header" id="header">
    <div class="container">
        <div class="header-inner">
            <a href="index.php" class="logo">
                <img src="assets/images/logo.png" alt="Gelora Gerung" class="logo-image">
            </a>
            
            <nav class="nav">
                <ul class="nav-list">
                    <li><a href="index.php" class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">Beranda</a></li>
                    <li><a href="jadwal.php" class="nav-link <?php echo $currentPage === 'jadwal' ? 'active' : ''; ?>">Jadwal</a></li>
                    <li><a href="harga.php" class="nav-link <?php echo $currentPage === 'harga' ? 'active' : ''; ?>">Harga</a></li>
                    <li><a href="tentang.php" class="nav-link <?php echo $currentPage === 'tentang' ? 'active' : ''; ?>">Tentang</a></li>
                    <li><a href="kontak.php" class="nav-link <?php echo $currentPage === 'kontak' ? 'active' : ''; ?>">Kontak</a></li>
                </ul>
                
                <div class="nav-cta">
                    <a href="booking.php" class="btn btn-primary">
                        <i data-lucide="calendar-plus" width="18" height="18"></i>
                        Booking Sekarang
                    </a>
                </div>
            </nav>
            
            <button class="menu-toggle" id="menuToggle" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</header>

<!-- Mobile Navigation Overlay -->
<div class="mobile-nav-overlay" id="mobileNavOverlay"></div>
<nav class="mobile-nav" id="mobileNav">
    <div class="mobile-nav-header">
        <a href="index.php" class="logo">
            <img src="assets/images/logo.png" alt="Gelora Gerung" class="logo-image">
        </a>
        <button class="mobile-nav-close" id="mobileNavClose">
            <i data-lucide="x" width="24" height="24"></i>
        </button>
    </div>
    <ul class="mobile-nav-list">
        <li><a href="index.php" class="<?php echo $currentPage === 'index' ? 'active' : ''; ?>">Beranda</a></li>
        <li><a href="jadwal.php" class="<?php echo $currentPage === 'jadwal' ? 'active' : ''; ?>">Jadwal</a></li>
        <li><a href="harga.php" class="<?php echo $currentPage === 'harga' ? 'active' : ''; ?>">Harga</a></li>
        <li><a href="tentang.php" class="<?php echo $currentPage === 'tentang' ? 'active' : ''; ?>">Tentang</a></li>
        <li><a href="kontak.php" class="<?php echo $currentPage === 'kontak' ? 'active' : ''; ?>">Kontak</a></li>
    </ul>
    <div class="mobile-nav-cta">
        <a href="booking.php" class="btn btn-primary btn-block">
            <i data-lucide="calendar-plus" width="18" height="18"></i>
            Booking Sekarang
        </a>
    </div>
</nav>

<style>
.mobile-nav-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 998;
    opacity: 0;
    transition: opacity var(--transition-base);
}

.mobile-nav-overlay.active {
    display: block;
    opacity: 1;
}

.mobile-nav {
    position: fixed;
    top: 0;
    right: -100%;
    width: 300px;
    max-width: 85%;
    height: 100vh;
    background: var(--white);
    z-index: 999;
    display: flex;
    flex-direction: column;
    transition: right var(--transition-base);
    box-shadow: var(--shadow-2xl);
}

.mobile-nav.active {
    right: 0;
}

.mobile-nav-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-lg);
    border-bottom: 1px solid var(--gray-100);
}

.mobile-nav-close {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-md);
    border: none;
    background: var(--gray-100);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray-600);
}

.mobile-nav-list {
    list-style: none;
    padding: var(--space-md);
    flex: 1;
    overflow-y: auto;
}

.mobile-nav-list li {
    margin-bottom: var(--space-xs);
}

.mobile-nav-list a {
    display: block;
    padding: var(--space-md);
    color: var(--gray-700);
    font-weight: 500;
    border-radius: var(--radius-md);
    transition: all var(--transition-fast);
}

.mobile-nav-list a:hover,
.mobile-nav-list a.active {
    background: var(--primary-50);
    color: var(--primary-600);
}

.mobile-nav-cta {
    padding: var(--space-lg);
    border-top: 1px solid var(--gray-100);
}

@media (min-width: 769px) {
    .mobile-nav,
    .mobile-nav-overlay {
        display: none !important;
    }
}
</style>
