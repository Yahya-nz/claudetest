<?php
require_once '../includes/config.php';
requireLogin();

$db = getDB();
$pageTitle = 'Tanggal Operasional';

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update operation start date
    if (isset($_POST['update_start_date'])) {
        $startDate = $_POST['operation_start_date'];
        $stmt = $db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'operation_start_date'");
        $stmt->execute([$startDate]);
        $message = 'Tanggal mulai operasional berhasil diperbarui.';
        $messageType = 'success';
    }

    // Add closed date
    if (isset($_POST['add_closed_date'])) {
        $closedDate = $_POST['closed_date'];
        $reason = $_POST['reason'] ?? '';

        try {
            $stmt = $db->prepare("INSERT INTO closed_dates (closed_date, reason) VALUES (?, ?)");
            $stmt->execute([$closedDate, $reason]);
            $message = 'Tanggal libur berhasil ditambahkan.';
            $messageType = 'success';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = 'Tanggal ini sudah ada dalam daftar tanggal libur.';
                $messageType = 'error';
            } else {
                $message = 'Terjadi kesalahan saat menambahkan tanggal libur.';
                $messageType = 'error';
            }
        }
    }
}

// Handle delete closed date
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM closed_dates WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Tanggal libur berhasil dihapus.';
    $messageType = 'success';
}

// Get operation start date
$startDateStmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'operation_start_date'");
$startDateStmt->execute();
$startDateResult = $startDateStmt->fetch();
$operationStartDate = $startDateResult ? $startDateResult['setting_value'] : '2026-02-02';

// Get all closed dates
$closedDates = $db->query("SELECT * FROM closed_dates ORDER BY closed_date DESC")->fetchAll();
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
                        <h1 class="page-title">Tanggal Operasional</h1>
                        <p class="page-subtitle">Kelola tanggal mulai buka dan tanggal libur lapangan</p>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?> mb-4">
                    <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" width="20" height="20"></i>
                    <span><?php echo $message; ?></span>
                </div>
                <?php endif; ?>

                <div class="d-grid" style="grid-template-columns: 2fr 1fr; gap: var(--space-xl);">
                    <!-- Left Column -->
                    <div>
                        <!-- Operation Start Date -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i data-lucide="calendar-plus" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                    Tanggal Mulai Buka
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-3" style="background-color: #e0f2fe; border-left: 4px solid #0ea5e9; padding: 12px;">
                                    <p style="margin: 0; font-size: 13px; color: #075985;">
                                        <strong>Info:</strong> Tentukan tanggal pertama lapangan dibuka untuk booking. Customer hanya dapat melakukan booking mulai dari tanggal ini.
                                    </p>
                                </div>
                                <form method="POST">
                                    <div class="form-group">
                                        <label class="form-label">Tanggal Mulai Operasional *</label>
                                        <input type="date" name="operation_start_date" class="form-input" value="<?php echo htmlspecialchars($operationStartDate); ?>" required>
                                        <p class="form-hint">Customer dapat melakukan booking mulai dari tanggal ini</p>
                                    </div>
                                    <button type="submit" name="update_start_date" class="btn btn-primary">
                                        <i data-lucide="save" width="18" height="18"></i>
                                        Perbarui Tanggal
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Closed Dates List -->
                        <div class="data-table-container">
                            <div class="data-table-header">
                                <h3 class="data-table-title">Daftar Tanggal Libur</h3>
                                <p style="font-size: 14px; color: var(--gray-600); margin-top: 4px;">
                                    Total: <?php echo count($closedDates); ?> tanggal libur
                                </p>
                            </div>
                            <?php if (empty($closedDates)): ?>
                            <div style="padding: var(--space-xl); text-align: center; color: var(--gray-500);">
                                <i data-lucide="calendar-off" width="48" height="48" style="margin-bottom: var(--space-md); opacity: 0.5;"></i>
                                <p style="margin: 0;">Belum ada tanggal libur yang ditambahkan</p>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Hari</th>
                                        <th>Alasan</th>
                                        <th>Ditambahkan</th>
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

                                    foreach ($closedDates as $date):
                                        $timestamp = strtotime($date['closed_date']);
                                        $dayName = $indonesianDays[date('l', $timestamp)];
                                        $isPast = $timestamp < strtotime('today');
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
                                        <td style="font-size: 13px; color: var(--gray-600);">
                                            <?php echo date('d/m/Y H:i', strtotime($date['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                <a href="?delete=<?php echo $date['id']; ?>" class="action-btn action-btn-delete" title="Hapus" onclick="return confirm('Hapus tanggal libur ini?')">
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

                    <!-- Right Column - Add Closed Date Form -->
                    <div>
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i data-lucide="plus-circle" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                    Tambah Tanggal Libur
                                </h4>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="form-group">
                                        <label class="form-label">Tanggal Libur *</label>
                                        <input type="date" name="closed_date" class="form-input" required min="<?php echo date('Y-m-d'); ?>">
                                        <p class="form-hint">Pilih tanggal ketika lapangan tutup</p>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Alasan (Opsional)</label>
                                        <textarea name="reason" class="form-textarea" rows="3" placeholder="Contoh: Hari Raya Nyepi, Maintenance, dll"></textarea>
                                        <p class="form-hint">Alasan penutupan lapangan</p>
                                    </div>
                                    <button type="submit" name="add_closed_date" class="btn btn-primary btn-block">
                                        <i data-lucide="plus" width="18" height="18"></i>
                                        Tambah Tanggal Libur
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Info Card -->
                        <div class="alert alert-info mt-4">
                            <i data-lucide="info" width="20" height="20"></i>
                            <div>
                                <strong>Catatan:</strong>
                                <ul style="margin: var(--space-sm) 0 0; padding-left: var(--space-lg);">
                                    <li>Tanggal libur tidak akan tersedia untuk booking</li>
                                    <li>Customer tidak dapat melakukan booking pada tanggal yang ditandai libur</li>
                                    <li>Tanggal yang sudah lewat akan tetap tersimpan untuk keperluan histori</li>
                                    <li>Pastikan untuk menginformasikan customer tentang perubahan jadwal</li>
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