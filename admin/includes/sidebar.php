<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <!-- Collapse Toggle Button -->
    <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" title="Toggle Sidebar">
        <i data-lucide="chevron-left" width="16" height="16"></i>
    </button>

    <div class="sidebar-header">
        <a href="index.php" class="sidebar-logo">
            <img src="../assets/images/logo.png" alt="Gelora Gerung">
        </a>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title">Menu Utama</span>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-item-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>" data-tooltip="Dashboard">
                        <i data-lucide="layout-dashboard" class="nav-item-icon" width="20" height="20"></i>
                        <span class="nav-item-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="bookings.php" class="nav-item-link <?php echo $currentPage === 'bookings' ? 'active' : ''; ?>" data-tooltip="Booking">
                        <i data-lucide="calendar-check" class="nav-item-icon" width="20" height="20"></i>
                        <span class="nav-item-text">Booking</span>
                        <?php
                        $db = getDB();
                        $pendingCount = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
                        if ($pendingCount > 0):
                        ?>
                        <span class="nav-item-badge"><?php echo $pendingCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="schedule.php" class="nav-item-link <?php echo $currentPage === 'schedule' ? 'active' : ''; ?>" data-tooltip="Jadwal">
                        <i data-lucide="calendar" class="nav-item-icon" width="20" height="20"></i>
                        <span class="nav-item-text">Jadwal</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="nav-section">
            <span class="nav-section-title">Pengaturan</span>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="prices.php" class="nav-item-link <?php echo $currentPage === 'prices' ? 'active' : ''; ?>" data-tooltip="Harga">
                        <i data-lucide="tag" class="nav-item-icon" width="20" height="20"></i>
                        <span class="nav-item-text">Harga</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="promos.php" class="nav-item-link <?php echo $currentPage === 'promos' ? 'active' : ''; ?>" data-tooltip="Promo & Diskon">
                        <i data-lucide="percent" class="nav-item-icon" width="20" height="20"></i>
                        <span class="nav-item-text">Promo & Diskon</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="time-slots.php" class="nav-item-link <?php echo $currentPage === 'time-slots' ? 'active' : ''; ?>" data-tooltip="Jam Operasional">
                        <i data-lucide="clock" class="nav-item-icon" width="20" height="20"></i>
                        <span class="nav-item-text">Jam Operasional</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="operational-dates.php" class="nav-item-link <?php echo $currentPage === 'operational-dates' ? 'active' : ''; ?>" data-tooltip="Tanggal Operasional">
                        <i data-lucide="calendar-off" class="nav-item-icon" width="20" height="20"></i>
                        <span class="nav-item-text">Tanggal Operasional</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="red-dates.php" class="nav-item-link <?php echo $currentPage === 'red-dates' ? 'active' : ''; ?>" data-tooltip="Tanggal Merah">
                        <i data-lucide="calendar-heart" class="nav-item-icon" width="20" height="20"></i>
                        <span class="nav-item-text">Tanggal Merah</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="services.php" class="nav-item-link <?php echo $currentPage === 'services' ? 'active' : ''; ?>" data-tooltip="Layanan Tambahan">
                        <i data-lucide="plus-circle" class="nav-item-icon" width="20" height="20"></i>
                        <span class="nav-item-text">Layanan Tambahan</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="facilities.php" class="nav-item-link <?php echo $currentPage === 'facilities' ? 'active' : ''; ?>" data-tooltip="Fasilitas">
                        <i data-lucide="home" class="nav-item-icon" width="20" height="20"></i>
                        <span class="nav-item-text">Fasilitas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="gallery.php" class="nav-item-link <?php echo $currentPage === 'gallery' ? 'active' : ''; ?>" data-tooltip="Galeri">
                        <i data-lucide="image" class="nav-item-icon" width="20" height="20"></i>
                        <span class="nav-item-text">Galeri</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Lainnya</span>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="members.php" class="nav-item-link <?php echo $currentPage === 'members' ? 'active' : ''; ?>" data-tooltip="Member">
                        <i data-lucide="users" class="nav-item-icon" width="20" height="20"></i>
                        <span class="nav-item-text">Member</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-item-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>" data-tooltip="Pengaturan Website">
                        <i data-lucide="settings" class="nav-item-icon" width="20" height="20"></i>
                        <span class="nav-item-text">Pengaturan Website</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></div>
                <div class="sidebar-user-role">Administrator</div>
            </div>
        </div>
    </div>
</aside>