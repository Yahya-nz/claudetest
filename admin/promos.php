<?php
require_once '../includes/config.php';
requireLogin();

$db = getDB();
$pageTitle = 'Kelola Promo & Diskon';

// Create table if not exists
$db->exec("
    CREATE TABLE IF NOT EXISTS promo_dates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        discount_percent INT NOT NULL,
        date_from DATE NOT NULL,
        date_to DATE NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_dates (date_from, date_to, is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Add announcement columns if not exists (migration)
try {
    $db->exec("ALTER TABLE promo_dates ADD COLUMN announcement_date DATE NULL AFTER date_to");
} catch (PDOException $e) {
    // Column already exists, ignore
}
try {
    $db->exec("ALTER TABLE promo_dates ADD COLUMN announcement_text VARCHAR(255) NULL AFTER announcement_date");
} catch (PDOException $e) {
    // Column already exists, ignore
}

$message = '';
$messageType = '';

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($action === 'delete' && $id > 0) {
        $stmt = $db->prepare("DELETE FROM promo_dates WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Promo berhasil dihapus.';
        $messageType = 'success';
    } elseif ($action === 'toggle' && $id > 0) {
        $stmt = $db->prepare("UPDATE promo_dates SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Status promo berhasil diubah.';
        $messageType = 'success';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $discountPercent = (int)($_POST['discount_percent'] ?? 0);
    $dateFrom = sanitize($_POST['date_from'] ?? '');
    $dateTo = sanitize($_POST['date_to'] ?? '');
    $announcementDate = sanitize($_POST['announcement_date'] ?? '');
    $announcementText = sanitize($_POST['announcement_text'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // If announcement_date empty, use date_from
    if (empty($announcementDate)) {
        $announcementDate = $dateFrom;
    }

    if (empty($name) || $discountPercent <= 0 || $discountPercent > 100 || empty($dateFrom) || empty($dateTo)) {
        $message = 'Mohon lengkapi semua field dengan benar.';
        $messageType = 'error';
    } elseif (strtotime($dateTo) < strtotime($dateFrom)) {
        $message = 'Tanggal selesai tidak boleh lebih kecil dari tanggal mulai.';
        $messageType = 'error';
    } elseif (!empty($announcementDate) && strtotime($announcementDate) > strtotime($dateFrom)) {
        $message = 'Tanggal mulai pemberitahuan tidak boleh lebih besar dari tanggal mulai promo.';
        $messageType = 'error';
    } else {
        $stmt = $db->prepare("INSERT INTO promo_dates (name, discount_percent, date_from, date_to, announcement_date, announcement_text, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $discountPercent, $dateFrom, $dateTo, $announcementDate, $announcementText, $isActive]);
        $message = 'Promo berhasil ditambahkan.';
        $messageType = 'success';
    }
}

// Get all promos
$promosStmt = $db->query("SELECT * FROM promo_dates ORDER BY date_from DESC");
$promos = $promosStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-main">
            <?php include 'includes/topbar.php'; ?>

            <div class="admin-content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Kelola Promo & Diskon</h1>
                        <p class="page-subtitle">Buat dan kelola promo diskon untuk tanggal tertentu</p>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?> mb-4">
                    <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" width="20" height="20"></i>
                    <span><?php echo $message; ?></span>
                </div>
                <?php endif; ?>

                <div class="d-grid" style="grid-template-columns: 1fr 2fr; gap: var(--space-xl);">
                    <!-- Add Promo Form -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">
                                <i data-lucide="plus-circle" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                Tambah Promo Baru
                            </h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label class="form-label">Nama Promo *</label>
                                    <input type="text" name="name" class="form-input" required placeholder="Contoh: Flash Sale 50%">
                                    <p class="form-hint">Nama promo yang akan ditampilkan</p>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Diskon (%) *</label>
                                    <input type="number" name="discount_percent" class="form-input" required min="1" max="100" value="50" placeholder="50">
                                    <p class="form-hint">Persentase diskon (1-100)</p>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Tanggal Mulai *</label>
                                    <input type="date" name="date_from" class="form-input" required min="<?php echo date('Y-m-d'); ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Tanggal Selesai *</label>
                                    <input type="date" name="date_to" class="form-input" required min="<?php echo date('Y-m-d'); ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Mulai Pemberitahuan</label>
                                    <input type="date" name="announcement_date" class="form-input" placeholder="<?php echo date('Y-m-d'); ?>">
                                    <p class="form-hint">Banner promo mulai muncul dari tanggal ini. Kosongkan untuk sama dengan Tanggal Mulai promo.</p>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Teks Pemberitahuan (Opsional)</label>
                                    <input type="text" name="announcement_text" class="form-input" placeholder="Contoh: Segera Hadir! Promo Spesial Grand Opening">
                                    <p class="form-hint">Teks khusus untuk banner sebelum promo aktif. Kosongkan untuk text default.</p>
                                </div>

                                <div class="form-group">
                                    <label class="form-checkbox">
                                        <input type="checkbox" name="is_active" checked>
                                        <span>Aktifkan promo</span>
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary w-full">
                                    <i data-lucide="plus" width="18" height="18"></i>
                                    Tambah Promo
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Promos List -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">
                                <i data-lucide="tag" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                Daftar Promo
                            </h4>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <?php if (empty($promos)): ?>
                            <div style="padding: var(--space-xl); text-align: center; color: var(--gray-500);">
                                <i data-lucide="inbox" width="48" height="48" style="margin-bottom: var(--space-md); opacity: 0.5;"></i>
                                <p>Belum ada promo. Silakan tambahkan promo baru.</p>
                            </div>
                            <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Nama Promo</th>
                                            <th>Diskon</th>
                                            <th>Periode Promo</th>
                                            <th>Banner Muncul</th>
                                            <th>Status</th>
                                            <th style="width: 120px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($promos as $promo):
                                            $isActive = $promo['is_active'];
                                            $isCurrent = date('Y-m-d') >= $promo['date_from'] && date('Y-m-d') <= $promo['date_to'];
                                            $isExpired = date('Y-m-d') > $promo['date_to'];
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($promo['name']); ?></strong>
                                                <?php if ($isCurrent && $isActive): ?>
                                                <span class="badge" style="background-color: #10b981; color: white; margin-left: 8px;">Sedang Berlangsung</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span style="color: var(--primary-600); font-weight: bold; font-size: 18px;"><?php echo $promo['discount_percent']; ?>%</span>
                                            </td>
                                            <td>
                                                <div style="font-size: 13px;">
                                                    <div><?php echo date('d M Y', strtotime($promo['date_from'])); ?></div>
                                                    <div style="color: var(--gray-500);">sampai</div>
                                                    <div><?php echo date('d M Y', strtotime($promo['date_to'])); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-size: 13px;">
                                                    <?php
                                                    $announcementDate = $promo['announcement_date'] ?? $promo['date_from'];
                                                    $isAnnounced = date('Y-m-d') >= $announcementDate;
                                                    ?>
                                                    <div><?php echo date('d M Y', strtotime($announcementDate)); ?></div>
                                                    <?php if ($isAnnounced && !$isCurrent): ?>
                                                    <span class="badge" style="background: #f59e0b; color: white; font-size: 10px; margin-top: 4px;">Segera Hadir</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($isExpired): ?>
                                                <span class="badge badge-secondary">Expired</span>
                                                <?php elseif ($isActive): ?>
                                                <span class="badge badge-success">Aktif</span>
                                                <?php else: ?>
                                                <span class="badge badge-warning">Nonaktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="?action=toggle&id=<?php echo $promo['id']; ?>" class="btn btn-sm btn-secondary" title="<?php echo $isActive ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                                        <i data-lucide="<?php echo $isActive ? 'eye-off' : 'eye'; ?>" width="16" height="16"></i>
                                                    </a>
                                                    <a href="?action=delete&id=<?php echo $promo['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus promo ini?')" title="Hapus">
                                                        <i data-lucide="trash-2" width="16" height="16"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h4 style="margin: 0 0 var(--space-md) 0; color: var(--primary-700);">
                            <i data-lucide="info" width="20" height="20" style="vertical-align: middle; margin-right: 8px;"></i>
                            Cara Kerja Promo
                        </h4>
                        <ul style="margin: 0; padding-left: 20px; color: var(--gray-600);">
                            <li><strong>Periode Promo:</strong> Tanggal di mana diskon akan diterapkan untuk booking</li>
                            <li><strong>Banner Muncul:</strong> Tanggal mulai ditampilkan banner promo (bisa sebelum promo aktif untuk pemberitahuan awal)</li>
                            <li><strong>Contoh:</strong> Promo aktif 2-28 Feb, banner muncul mulai 23 Jan → Customer dapat info promo lebih awal!</li>
                            <li>Diskon otomatis diterapkan untuk booking pada tanggal dalam periode promo</li>
                            <li>Hanya satu promo yang berlaku per booking (promo dengan diskon terbesar)</li>
                            <li>Promo bisa diaktifkan/nonaktifkan tanpa menghapus data</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/admin-scripts.php'; ?>

    <style>
        @media (max-width: 1024px) {
            .d-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</body>
</html>