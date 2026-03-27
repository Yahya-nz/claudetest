<?php
require_once '../includes/config.php';
requireLogin();

$db = getDB();
$pageTitle = 'Layanan Tambahan';

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_service'])) {
        $name = sanitize($_POST['name']);
        $price = (float)$_POST['price'];
        $description = sanitize($_POST['description']);
        
        $stmt = $db->prepare("INSERT INTO additional_services (name, price, description) VALUES (?, ?, ?)");
        $stmt->execute([$name, $price, $description]);
        $message = 'Layanan berhasil ditambahkan.';
        $messageType = 'success';
    }
    
    if (isset($_POST['update_service'])) {
        $id = (int)$_POST['service_id'];
        $name = sanitize($_POST['name']);
        $price = (float)$_POST['price'];
        $description = sanitize($_POST['description']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $db->prepare("UPDATE additional_services SET name = ?, price = ?, description = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$name, $price, $description, $isActive, $id]);
        $message = 'Layanan berhasil diperbarui.';
        $messageType = 'success';
    }
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE additional_services SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Status layanan berhasil diubah.';
    $messageType = 'success';
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM additional_services WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Layanan berhasil dihapus.';
    $messageType = 'success';
}

// Get services
$services = $db->query("SELECT * FROM additional_services ORDER BY name")->fetchAll();

// Edit mode
$editService = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM additional_services WHERE id = ?");
    $stmt->execute([$editId]);
    $editService = $stmt->fetch();
}
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
                        <h1 class="page-title">Layanan Tambahan</h1>
                        <p class="page-subtitle">Kelola layanan tambahan seperti fotografer, wasit, dll</p>
                    </div>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?> mb-4">
                    <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" width="20" height="20"></i>
                    <span><?php echo $message; ?></span>
                </div>
                <?php endif; ?>
                
                <div class="d-grid" style="grid-template-columns: 2fr 1fr; gap: var(--space-xl);">
                    <!-- Services List -->
                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Daftar Layanan</h3>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nama Layanan</th>
                                    <th>Harga</th>
                                    <th>Deskripsi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($services)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted" style="padding: var(--space-xl);">Belum ada layanan</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($services as $service): ?>
                                <tr>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($service['name']); ?></td>
                                    <td style="color: var(--primary-600); font-weight: 600;"><?php echo formatRupiah($service['price']); ?></td>
                                    <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($service['description']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $service['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $service['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="?edit=<?php echo $service['id']; ?>" class="action-btn action-btn-edit" title="Edit">
                                                <i data-lucide="edit" width="16" height="16"></i>
                                            </a>
                                            <a href="?toggle=<?php echo $service['id']; ?>" class="action-btn action-btn-view" title="Toggle Status">
                                                <i data-lucide="<?php echo $service['is_active'] ? 'eye-off' : 'eye'; ?>" width="16" height="16"></i>
                                            </a>
                                            <a href="?delete=<?php echo $service['id']; ?>" class="action-btn action-btn-delete" title="Hapus" onclick="return confirm('Hapus layanan ini?')">
                                                <i data-lucide="trash-2" width="16" height="16"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Add/Edit Form -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">
                                <i data-lucide="<?php echo $editService ? 'edit' : 'plus-circle'; ?>" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                <?php echo $editService ? 'Edit Layanan' : 'Tambah Layanan Baru'; ?>
                            </h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?php if ($editService): ?>
                                <input type="hidden" name="service_id" value="<?php echo $editService['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label class="form-label">Nama Layanan</label>
                                    <input type="text" name="name" class="form-input" required value="<?php echo $editService ? htmlspecialchars($editService['name']) : ''; ?>" placeholder="Contoh: Fotografer">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Harga (Rp)</label>
                                    <input type="number" name="price" class="form-input" required value="<?php echo $editService ? $editService['price'] : ''; ?>" placeholder="250000">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea name="description" class="form-textarea" rows="3" placeholder="Deskripsi layanan..."><?php echo $editService ? htmlspecialchars($editService['description']) : ''; ?></textarea>
                                </div>
                                
                                <?php if ($editService): ?>
                                <div class="form-group">
                                    <label class="form-checkbox">
                                        <input type="checkbox" name="is_active" <?php echo $editService['is_active'] ? 'checked' : ''; ?>>
                                        <span>Aktif</span>
                                    </label>
                                </div>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" name="<?php echo $editService ? 'update_service' : 'add_service'; ?>" class="btn btn-primary flex-1">
                                        <i data-lucide="<?php echo $editService ? 'save' : 'plus'; ?>" width="18" height="18"></i>
                                        <?php echo $editService ? 'Simpan Perubahan' : 'Tambah Layanan'; ?>
                                    </button>
                                    <?php if ($editService): ?>
                                    <a href="services.php" class="btn btn-outline">Batal</a>
                                    <?php endif; ?>
                                </div>
                            </form>
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
        .flex-1 { flex: 1; }
    </style>
</body>
</html>
