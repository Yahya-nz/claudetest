<?php
require_once '../includes/config.php';
requireLogin();

$db = getDB();
$pageTitle = 'Jam Operasional';

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_slot'])) {
        $startTime = $_POST['start_time'];
        $endTime = $_POST['end_time'];
        
        $stmt = $db->prepare("INSERT INTO time_slots (start_time, end_time) VALUES (?, ?)");
        $stmt->execute([$startTime, $endTime]);
        $message = 'Slot waktu berhasil ditambahkan.';
        $messageType = 'success';
    }
    
    if (isset($_POST['update_slot'])) {
        $id = (int)$_POST['slot_id'];
        $startTime = $_POST['start_time'];
        $endTime = $_POST['end_time'];
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $db->prepare("UPDATE time_slots SET start_time = ?, end_time = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$startTime, $endTime, $isActive, $id]);
        $message = 'Slot waktu berhasil diperbarui.';
        $messageType = 'success';
    }
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE time_slots SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Status slot waktu berhasil diubah.';
    $messageType = 'success';
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Check if slot is used in any booking
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE time_slot_id = ?");
    $checkStmt->execute([$id]);
    if ($checkStmt->fetchColumn() > 0) {
        $message = 'Tidak dapat menghapus slot yang sudah digunakan dalam booking.';
        $messageType = 'error';
    } else {
        $stmt = $db->prepare("DELETE FROM time_slots WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Slot waktu berhasil dihapus.';
        $messageType = 'success';
    }
}

// Get time slots
$timeSlots = $db->query("SELECT * FROM time_slots ORDER BY start_time")->fetchAll();
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
                        <h1 class="page-title">Jam Operasional</h1>
                        <p class="page-subtitle">Kelola slot waktu yang tersedia untuk booking</p>
                    </div>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?> mb-4">
                    <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" width="20" height="20"></i>
                    <span><?php echo $message; ?></span>
                </div>
                <?php endif; ?>
                
                <div class="d-grid" style="grid-template-columns: 2fr 1fr; gap: var(--space-xl);">
                    <!-- Time Slots List -->
                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Daftar Slot Waktu</h3>
                        </div>
                        <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Jam Mulai</th>
                                    <th>Jam Selesai</th>
                                    <th>Durasi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($timeSlots as $slot): 
                                    $start = strtotime($slot['start_time']);
                                    $end = strtotime($slot['end_time']);
                                    $duration = ($end - $start) / 3600;
                                ?>
                                <tr>
                                    <td style="font-weight: 600;"><?php echo date('H:i', $start); ?></td>
                                    <td style="font-weight: 600;"><?php echo date('H:i', $end); ?></td>
                                    <td><?php echo $duration; ?> jam</td>
                                    <td>
                                        <span class="badge <?php echo $slot['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $slot['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="?toggle=<?php echo $slot['id']; ?>" class="action-btn action-btn-edit" title="Toggle Status">
                                                <i data-lucide="<?php echo $slot['is_active'] ? 'eye-off' : 'eye'; ?>" width="16" height="16"></i>
                                            </a>
                                            <a href="?delete=<?php echo $slot['id']; ?>" class="action-btn action-btn-delete" title="Hapus" onclick="return confirm('Hapus slot waktu ini?')">
                                                <i data-lucide="trash-2" width="16" height="16"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    
                    <!-- Add New Slot -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">
                                <i data-lucide="plus-circle" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                Tambah Slot Baru
                            </h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="form-group">
                                    <label class="form-label">Jam Mulai</label>
                                    <input type="time" name="start_time" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Jam Selesai</label>
                                    <input type="time" name="end_time" class="form-input" required>
                                </div>
                                <button type="submit" name="add_slot" class="btn btn-primary btn-block">
                                    <i data-lucide="plus" width="18" height="18"></i>
                                    Tambah Slot
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Info -->
                <div class="alert alert-info mt-4">
                    <i data-lucide="info" width="20" height="20"></i>
                    <div>
                        <strong>Catatan:</strong>
                        <ul style="margin: var(--space-sm) 0 0; padding-left: var(--space-lg);">
                            <li>Slot waktu yang dinonaktifkan tidak akan muncul di halaman booking</li>
                            <li>Slot waktu yang sudah digunakan dalam booking tidak dapat dihapus</li>
                            <li>Pastikan untuk menambahkan harga untuk setiap slot waktu baru di menu Harga</li>
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