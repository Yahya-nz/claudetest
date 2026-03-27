<?php
require_once '../includes/config.php';
requireLogin();

$db = getDB();
$pageTitle = 'Pengaturan Harga';

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_prices'])) {
        foreach ($_POST['prices'] as $id => $price) {
            $stmt = $db->prepare("UPDATE price_settings SET price = ? WHERE id = ?");
            $stmt->execute([(float)$price, (int)$id]);
        }
        $message = 'Harga berhasil diperbarui.';
        $messageType = 'success';
    }
    
    if (isset($_POST['add_price'])) {
        $dayType = $_POST['day_type'];
        $timeSlot = $_POST['time_slot'];
        $price = (float)$_POST['price'];
        
        $stmt = $db->prepare("INSERT INTO price_settings (day_type, time_slot, price) VALUES (?, ?, ?)");
        $stmt->execute([$dayType, $timeSlot, $price]);
        $message = 'Harga baru berhasil ditambahkan.';
        $messageType = 'success';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM price_settings WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Harga berhasil dihapus.';
    $messageType = 'success';
}

// Get prices
$weekdayPrices = $db->query("SELECT * FROM price_settings WHERE day_type = 'weekday' ORDER BY time_slot")->fetchAll();
$weekendPrices = $db->query("SELECT * FROM price_settings WHERE day_type = 'weekend' ORDER BY time_slot")->fetchAll();
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
                        <h1 class="page-title">Pengaturan Harga</h1>
                        <p class="page-subtitle">Kelola harga sewa lapangan untuk weekday dan weekend</p>
                    </div>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?> mb-4">
                    <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" width="20" height="20"></i>
                    <span><?php echo $message; ?></span>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="d-grid" style="grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: var(--space-xl);">
                        <!-- Weekday Prices -->
                        <div class="card">
                            <div class="card-header" style="background: var(--primary-600); color: white;">
                                <h4 class="mb-0" style="color: white;">
                                    <i data-lucide="briefcase" width="20" height="20" style="vertical-align: middle; margin-right: 8px;"></i>
                                    Harga Weekday (Senin - Jumat)
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Jam</th>
                                            <th>Harga (Rp)</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($weekdayPrices as $price): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($price['time_slot']); ?></td>
                                            <td>
                                                <input type="number" name="prices[<?php echo $price['id']; ?>]" value="<?php echo $price['price']; ?>" class="form-input" style="width: 150px;">
                                            </td>
                                            <td>
                                                <a href="?delete=<?php echo $price['id']; ?>" class="action-btn action-btn-delete" onclick="return confirm('Hapus harga ini?')">
                                                    <i data-lucide="trash-2" width="16" height="16"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                </div>
                            </div>
                        </div>

                        <!-- Weekend Prices -->
                        <div class="card">
                            <div class="card-header" style="background: var(--accent-gold); color: var(--gray-900);">
                                <h4 class="mb-0" style="color: var(--gray-900);">
                                    <i data-lucide="sun" width="20" height="20" style="vertical-align: middle; margin-right: 8px;"></i>
                                    Harga Weekend / Libur
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Jam</th>
                                            <th>Harga (Rp)</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($weekendPrices as $price): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($price['time_slot']); ?></td>
                                            <td>
                                                <input type="number" name="prices[<?php echo $price['id']; ?>]" value="<?php echo $price['price']; ?>" class="form-input" style="width: 150px;">
                                            </td>
                                            <td>
                                                <a href="?delete=<?php echo $price['id']; ?>" class="action-btn action-btn-delete" onclick="return confirm('Hapus harga ini?')">
                                                    <i data-lucide="trash-2" width="16" height="16"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" name="update_prices" class="btn btn-primary btn-lg">
                            <i data-lucide="save" width="20" height="20"></i>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
                
                <!-- Add New Price -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i data-lucide="plus" width="20" height="20" style="vertical-align: middle; margin-right: 8px;"></i>
                            Tambah Harga Baru
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="d-flex gap-3 flex-wrap align-center">
                                <div class="form-group mb-0">
                                    <label class="form-label">Tipe Hari</label>
                                    <select name="day_type" class="form-select" required style="width: 150px;">
                                        <option value="weekday">Weekday</option>
                                        <option value="weekend">Weekend</option>
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Slot Waktu</label>
                                    <input type="text" name="time_slot" class="form-input" placeholder="06:00-08:00" required style="width: 150px;">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label">Harga (Rp)</label>
                                    <input type="number" name="price" class="form-input" placeholder="400000" required style="width: 150px;">
                                </div>
                                <div class="form-group mb-0" style="padding-top: 28px;">
                                    <button type="submit" name="add_price" class="btn btn-primary">
                                        <i data-lucide="plus" width="18" height="18"></i>
                                        Tambah
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/admin-scripts.php'; ?>

    <style>
        @media (max-width: 768px) {
            .d-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</body>
</html>