<script>
    // Initialize Lucide icons
    lucide.createIcons();

    // Sidebar elements
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const adminMain = document.querySelector('.admin-main');
    const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');

    // Mobile sidebar toggle (hamburger menu)
    function toggleMobileSidebar() {
        sidebar.classList.toggle('open');
        sidebarOverlay?.classList.toggle('active');
    }

    sidebarToggle?.addEventListener('click', toggleMobileSidebar);
    sidebarOverlay?.addEventListener('click', toggleMobileSidebar);

    // Desktop sidebar collapse (icon-only mode)
    function collapseSidebar() {
        sidebar.classList.toggle('collapsed');
        adminMain.classList.toggle('sidebar-collapsed');

        // Save state to localStorage
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);

        // Re-initialize icons after toggle
        setTimeout(() => lucide.createIcons(), 100);
    }

    sidebarCollapseBtn?.addEventListener('click', collapseSidebar);

    // Restore sidebar state from localStorage (only on desktop)
    function restoreSidebarState() {
        if (window.innerWidth > 1024) {
            const savedSidebarState = localStorage.getItem('sidebarCollapsed');
            if (savedSidebarState === 'true') {
                sidebar.classList.add('collapsed');
                adminMain.classList.add('sidebar-collapsed');
            }
        }
    }

    restoreSidebarState();

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            // Desktop: restore collapse state
            restoreSidebarState();
            sidebar.classList.remove('open');
            sidebarOverlay?.classList.remove('active');
        } else {
            // Mobile: reset to default (full sidebar when opened)
            sidebar.classList.remove('collapsed');
            adminMain.classList.remove('sidebar-collapsed');
        }
    });

    // Table scroll hint functionality
    function initTableScrollHint() {
        const tableWrapper = document.getElementById('tableWrapper');
        const scrollHint = document.getElementById('scrollHint');
        if (tableWrapper && scrollHint) {
            if (tableWrapper.scrollWidth > tableWrapper.clientWidth) {
                scrollHint.style.display = 'block';
                // Hide hint after first scroll
                tableWrapper.addEventListener('scroll', function() {
                    scrollHint.style.display = 'none';
                }, { once: true });
            }
        }
    }

    // Initialize table scroll hint
    initTableScrollHint();
    window.addEventListener('resize', initTableScrollHint);
</script>