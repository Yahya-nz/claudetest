<?php
require_once '../includes/config.php';
requireLogin();

$db = getDB();
$pageTitle = 'Tanggal Merah';

// Create red_dates table if not exists
$db->exec("
    CREATE TABLE IF NOT EXISTS red_dates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        red_date DATE NOT NULL UNIQUE,
        reason VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_red_date (red_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_red_date'])) {
        $redDate = $_POST['red_date'];
        $reason = $_POST['reason'] ?? '';

        try {
            $stmt = $db->prepare("INSERT INTO red_dates (red_date, reason) VALUES (?, ?)");
            $stmt->execute([$redDate, $reason]);
            $message = 'Tanggal merah berhasil ditambahkan. Tanggal ini akan menggunakan harga weekend.';
            $messageType = 'success';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = 'Tanggal ini sudah ada dalam daftar tanggal merah.';
                $messageType = 'error';
            } else {
                $message = 'Terjadi kesalahan saat menambahkan tanggal merah.';
                $messageType = 'error';
            }
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM red_dates WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Tanggal merah berhasil dihapus.';
    $messageType = 'success';
}

// Get all red dates
$redDates = $db->query("SELECT * FROM red_dates ORDER BY red_date ASC")->fetchAll();
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
                        <h1 class="page-title">Tanggal Merah (Hari Libur Nasional)</h1>
                        <p class="page-subtitle">Kelola tanggal merah yang menggunakan harga weekend</p>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?> mb-4">
                    <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" width="20" height="20"></i>
                    <span><?php echo $message; ?></span>
                </div>
                <?php endif; ?>

                <div class="d-grid" style="grid-template-columns: 2fr 1fr; gap: var(--space-xl);">
                    <!-- Left Column - Red Dates List -->
                    <div>
                        <div class="data-table-container">
                            <div class="data-table-header">
                                <h3 class="data-table-title">Daftar Tanggal Merah</h3>
                                <p style="font-size: 14px; color: var(--gray-600); margin-top: 4px;">
                                    Total: <?php echo count($redDates); ?> tanggal merah
                                </p>
                            </div>
                            <?php if (empty($redDates)): ?>
                            <div style="padding: var(--space-xl); text-align: center; color: var(--gray-500);">
                                <i data-lucide="calendar-heart" width="48" height="48" style="margin-bottom: var(--space-md); opacity: 0.5;"></i>
                                <p style="margin: 0;">Belum ada tanggal merah yang ditambahkan</p>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Hari</th>
                                        <th>Keterangan</th>
                                        <th>Harga</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $indonesianDays = [
                                        'Sunday' => 'Minggu',
                                        'Monday' => 'Senin',
                                        'Tuesday' => 'Selasa',
                                        'Wednesday' => 'Rabu',
                                        'Thursday' => 'Kamis',
                                        'Friday' => 'Jumat',
                                        'Saturday' => 'Sabtu'
                                    ];

                                    foreach ($redDates as $date):
                                        $timestamp = strtotime($date['red_date']);
                                        $dayName = $indonesianDays[date('l', $timestamp)];
                                        $isPast = $timestamp < strtotime('today');
                                        $dayOfWeek = date('N', $timestamp);
                                        $isAlreadyWeekend = $dayOfWeek >= 6;
                                    ?>
                                    <tr <?php echo $isPast ? 'style="opacity: 0.6;"' : ''; ?>>
                                        <td style="font-weight: 600;">
                                            <?php echo date('d/m/Y', $timestamp); ?>
                                            <?php if ($isPast): ?>
                                            <span class="badge" style="background-color: var(--gray-200); color: var(--gray-600); font-size: 11px; margin-left: 4px;">Lewat</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $dayName; ?></td>
                                        <td><?php echo htmlspecialchars($date['reason']) ?: '-'; ?></td>
                                        <td>
                                            <?php if ($isAlreadyWeekend): ?>
                                            <span class="badge badge-warning" style="font-size: 11px;">Sudah Weekend</span>
                                            <?php else: ?>
                                            <span class="badge badge-success" style="font-size: 11px;">Harga Weekend</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                <a href="?delete=<?php echo $date['id']; ?>" class="action-btn action-btn-delete" title="Hapus" onclick="return confirm('Hapus tanggal merah ini?')">
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

                    <!-- Right Column - Add Red Date Form -->
                    <div>
                        <div class="card">
                            <div class="card-header" style="background: #dc2626; color: white;">
                                <h4 class="mb-0" style="color: white;">
                                    <i data-lucide="plus-circle" width="20" height="20" style="vertical-align: middle; margin-right: 8px;"></i>
                                    Tambah Tanggal Merah
                                </h4>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="form-group">
                                        <label class="form-label">Tanggal Merah *</label>
                                        <input type="date" name="red_date" class="form-input" required>
                                        <p class="form-hint">Pilih tanggal hari libur nasional</p>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Keterangan (Opsional)</label>
                                        <textarea name="reason" class="form-textarea" rows="3" placeholder="Contoh: Hari Raya Idul Fitri, Tahun Baru Imlek, Hari Kemerdekaan, dll"></textarea>
                                        <p class="form-hint">Nama hari libur atau keterangan</p>
                                    </div>
                                    <button type="submit" name="add_red_date" class="btn btn-primary btn-block">
                                        <i data-lucide="plus" width="18" height="18"></i>
                                        Tambah Tanggal Merah
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Info Card -->
                        <div class="alert alert-info mt-4">
                            <i data-lucide="info" width="20" height="20"></i>
                            <div>
                                <strong>Tentang Tanggal Merah:</strong>
                                <ul style="margin: var(--space-sm) 0 0; padding-left: var(--space-lg);">
                                    <li>Tanggal merah adalah hari libur nasional yang jatuh pada hari kerja (Senin-Jumat)</li>
                                    <li>Pada tanggal merah, harga yang digunakan adalah <strong>harga weekend</strong></li>
                                    <li>Lapangan tetap buka pada tanggal merah (berbeda dengan tanggal libur/tutup)</li>
                                    <li>Jika tanggal merah jatuh pada Sabtu/Minggu, harga sudah otomatis weekend</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Difference Info -->
                        <div class="alert mt-3" style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px;">
                            <i data-lucide="alert-triangle" width="20" height="20" style="color: #f59e0b;"></i>
                            <div>
                                <strong style="color: #92400e;">Perbedaan dengan Tanggal Libur (Tutup):</strong>
                                <ul style="margin: var(--space-sm) 0 0; padding-left: var(--space-lg); color: #92400e; font-size: 13px;">
                                    <li><strong>Tanggal Merah:</strong> Lapangan BUKA, harga weekend</li>
                                    <li><strong>Tanggal Libur:</strong> Lapangan TUTUP, tidak bisa booking</li>
                                </ul>
                            </div>
                        </div>
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
