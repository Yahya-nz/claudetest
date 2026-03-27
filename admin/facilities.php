<?php
require_once '../includes/config.php';
requireLogin();

$db = getDB();
$pageTitle = 'Fasilitas';

$message = '';
$messageType = '';

// Available icons
$availableIcons = [
    'field' => 'Lapangan',
    'door' => 'Ruang Ganti',
    'car' => 'Parkir',
    'coffee' => 'Kantin',
    'droplet' => 'Toilet',
    'users' => 'Tribun',
    'wifi' => 'WiFi',
    'sun' => 'Lampu'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_facility'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $icon = sanitize($_POST['icon']);
        $sortOrder = (int)$_POST['sort_order'];
        
        $stmt = $db->prepare("INSERT INTO facilities (name, description, icon, sort_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, $icon, $sortOrder]);
        $message = 'Fasilitas berhasil ditambahkan.';
        $messageType = 'success';
    }
    
    if (isset($_POST['update_facility'])) {
        $id = (int)$_POST['facility_id'];
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $icon = sanitize($_POST['icon']);
        $sortOrder = (int)$_POST['sort_order'];
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $db->prepare("UPDATE facilities SET name = ?, description = ?, icon = ?, sort_order = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$name, $description, $icon, $sortOrder, $isActive, $id]);
        $message = 'Fasilitas berhasil diperbarui.';
        $messageType = 'success';
    }
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE facilities SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Status fasilitas berhasil diubah.';
    $messageType = 'success';
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM facilities WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Fasilitas berhasil dihapus.';
    $messageType = 'success';
}

// Get facilities
$facilities = $db->query("SELECT * FROM facilities ORDER BY sort_order, name")->fetchAll();

// Edit mode
$editFacility = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM facilities WHERE id = ?");
    $stmt->execute([$editId]);
    $editFacility = $stmt->fetch();
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
                        <h1 class="page-title">Fasilitas</h1>
                        <p class="page-subtitle">Kelola daftar fasilitas yang tersedia</p>
                    </div>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?> mb-4">
                    <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" width="20" height="20"></i>
                    <span><?php echo $message; ?></span>
                </div>
                <?php endif; ?>
                
                <div class="d-grid" style="grid-template-columns: 2fr 1fr; gap: var(--space-xl);">
                    <!-- Facilities List -->
                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Daftar Fasilitas</h3>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Urutan</th>
                                    <th>Icon</th>
                                    <th>Nama</th>
                                    <th>Deskripsi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($facilities)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted" style="padding: var(--space-xl);">Belum ada fasilitas</td>
                                </tr>
                                <?php else: ?>
                                <?php 
                                $iconMap = [
                                    'field' => 'layout-grid',
                                    'door' => 'door-open',
                                    'car' => 'car',
                                    'coffee' => 'coffee',
                                    'droplet' => 'droplets',
                                    'users' => 'users',
                                    'wifi' => 'wifi',
                                    'sun' => 'sun'
                                ];
                                foreach ($facilities as $facility): 
                                    $lucideIcon = $iconMap[$facility['icon']] ?? 'check-circle';
                                ?>
                                <tr>
                                    <td><?php echo $facility['sort_order']; ?></td>
                                    <td>
                                        <div style="width: 40px; height: 40px; background: var(--primary-50); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                                            <i data-lucide="<?php echo $lucideIcon; ?>" width="20" height="20" style="color: var(--primary-600);"></i>
                                        </div>
                                    </td>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($facility['name']); ?></td>
                                    <td style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($facility['description']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $facility['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $facility['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="?edit=<?php echo $facility['id']; ?>" class="action-btn action-btn-edit" title="Edit">
                                                <i data-lucide="edit" width="16" height="16"></i>
                                            </a>
                                            <a href="?toggle=<?php echo $facility['id']; ?>" class="action-btn action-btn-view" title="Toggle Status">
                                                <i data-lucide="<?php echo $facility['is_active'] ? 'eye-off' : 'eye'; ?>" width="16" height="16"></i>
                                            </a>
                                            <a href="?delete=<?php echo $facility['id']; ?>" class="action-btn action-btn-delete" title="Hapus" onclick="return confirm('Hapus fasilitas ini?')">
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
                                <i data-lucide="<?php echo $editFacility ? 'edit' : 'plus-circle'; ?>" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                <?php echo $editFacility ? 'Edit Fasilitas' : 'Tambah Fasilitas Baru'; ?>
                            </h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?php if ($editFacility): ?>
                                <input type="hidden" name="facility_id" value="<?php echo $editFacility['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label class="form-label">Nama Fasilitas</label>
                                    <input type="text" name="name" class="form-input" required value="<?php echo $editFacility ? htmlspecialchars($editFacility['name']) : ''; ?>" placeholder="Contoh: Lapangan Berstandar FIFA">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea name="description" class="form-textarea" rows="3" placeholder="Deskripsi fasilitas..."><?php echo $editFacility ? htmlspecialchars($editFacility['description']) : ''; ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Icon</label>
                                    <select name="icon" class="form-select" required>
                                        <?php foreach ($availableIcons as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo ($editFacility && $editFacility['icon'] === $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Urutan</label>
                                    <input type="number" name="sort_order" class="form-input" value="<?php echo $editFacility ? $editFacility['sort_order'] : '0'; ?>" min="0">
                                </div>
                                
                                <?php if ($editFacility): ?>
                                <div class="form-group">
                                    <label class="form-checkbox">
                                        <input type="checkbox" name="is_active" <?php echo $editFacility['is_active'] ? 'checked' : ''; ?>>
                                        <span>Aktif</span>
                                    </label>
                                </div>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" name="<?php echo $editFacility ? 'update_facility' : 'add_facility'; ?>" class="btn btn-primary flex-1">
                                        <i data-lucide="<?php echo $editFacility ? 'save' : 'plus'; ?>" width="18" height="18"></i>
                                        <?php echo $editFacility ? 'Simpan Perubahan' : 'Tambah Fasilitas'; ?>
                                    </button>
                                    <?php if ($editFacility): ?>
                                    <a href="facilities.php" class="btn btn-outline">Batal</a>
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
