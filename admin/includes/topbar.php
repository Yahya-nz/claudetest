<!-- Top Bar -->
<header class="admin-topbar">
    <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i data-lucide="menu" width="20" height="20"></i>
        </button>
        <h2 class="topbar-title"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h2>
    </div>
    
    <div class="topbar-right">
        <div class="topbar-search">
            <i data-lucide="search" width="18" height="18" class="topbar-search-icon"></i>
            <input type="text" placeholder="Cari..." class="form-input">
        </div>
        
        <div class="topbar-actions">
            <button class="topbar-action-btn" title="Notifikasi">
                <i data-lucide="bell" width="20" height="20"></i>
                <?php
                $db = getDB();
                $pendingCount = $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
                if ($pendingCount > 0):
                ?>
                <span class="notification-dot"></span>
                <?php endif; ?>
            </button>
            
            <a href="../index.php" class="topbar-action-btn" title="Lihat Website" target="_blank">
                <i data-lucide="external-link" width="20" height="20"></i>
            </a>
            
            <a href="logout.php" class="topbar-action-btn" title="Logout">
                <i data-lucide="log-out" width="20" height="20"></i>
            </a>
        </div>
    </div>
</header>
