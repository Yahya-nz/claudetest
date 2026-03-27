<?php
require_once '../includes/config.php';
requireLogin();

$db = getDB();
$pageTitle = 'Galeri';

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_gallery'])) {
        $caption = sanitize($_POST['caption']);
        $sortOrder = (int)$_POST['sort_order'];
        $imagePath = sanitize($_POST['image_path']);

        $stmt = $db->prepare("INSERT INTO gallery (image_path, caption, sort_order) VALUES (?, ?, ?)");
        $stmt->execute([$imagePath, $caption, $sortOrder]);
        $message = 'Foto berhasil ditambahkan ke galeri.';
        $messageType = 'success';
    }

    if (isset($_POST['update_gallery'])) {
        $id = (int)$_POST['gallery_id'];
        $caption = sanitize($_POST['caption']);
        $sortOrder = (int)$_POST['sort_order'];
        $imagePath = sanitize($_POST['image_path']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $stmt = $db->prepare("UPDATE gallery SET image_path = ?, caption = ?, sort_order = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$imagePath, $caption, $sortOrder, $isActive, $id]);
        $message = 'Foto berhasil diperbarui.';
        $messageType = 'success';
    }
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE gallery SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Status foto berhasil diubah.';
    $messageType = 'success';
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM gallery WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Foto berhasil dihapus dari galeri.';
    $messageType = 'success';
}

// Get gallery items
$galleryItems = $db->query("SELECT * FROM gallery ORDER BY sort_order, created_at DESC")->fetchAll();

// Edit mode
$editGallery = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM gallery WHERE id = ?");
    $stmt->execute([$editId]);
    $editGallery = $stmt->fetch();
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
                        <h1 class="page-title">Galeri Foto</h1>
                        <p class="page-subtitle">Kelola foto-foto fasilitas dan lapangan</p>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?> mb-4">
                    <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" width="20" height="20"></i>
                    <span><?php echo $message; ?></span>
                </div>
                <?php endif; ?>

                <div class="d-grid" style="grid-template-columns: 2fr 1fr; gap: var(--space-xl);">
                    <!-- Gallery Grid -->
                    <div>
                        <div class="data-table-container">
                            <div class="data-table-header">
                                <h3 class="data-table-title">
                                    <i data-lucide="image" width="20" height="20" style="vertical-align: middle; margin-right: 8px;"></i>
                                    Foto Galeri
                                </h3>
                                <span class="badge badge-primary"><?php echo count($galleryItems); ?> Foto</span>
                            </div>

                            <?php if (empty($galleryItems)): ?>
                            <div class="card-body">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i data-lucide="image-off" width="48" height="48"></i>
                                    </div>
                                    <h4 class="empty-state-title">Belum ada foto</h4>
                                    <p class="empty-state-text">Tambahkan foto pertama Anda menggunakan form di samping</p>
                                </div>
                            </div>
                            <?php else: ?>
                            <div style="padding: var(--space-lg);">
                                <div class="gallery-grid">
                                    <?php foreach ($galleryItems as $item): ?>
                                    <div class="gallery-item <?php echo !$item['is_active'] ? 'gallery-item-inactive' : ''; ?>">
                                        <div class="gallery-image">
                                            <?php if (!empty($item['image_path']) && file_exists('../' . $item['image_path'])): ?>
                                                <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['caption']); ?>">
                                            <?php else: ?>
                                                <div class="gallery-placeholder">
                                                    <i data-lucide="image" width="32" height="32"></i>
                                                    <span>No Image</span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!$item['is_active']): ?>
                                            <div class="gallery-overlay">
                                                <span class="badge badge-danger">Tidak Aktif</span>
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="gallery-info">
                                            <div class="gallery-caption">
                                                <?php echo htmlspecialchars($item['caption'] ?: 'Tanpa caption'); ?>
                                            </div>
                                            <div class="gallery-meta">
                                                <span class="badge badge-primary">Urutan: <?php echo $item['sort_order']; ?></span>
                                            </div>
                                        </div>

                                        <div class="gallery-actions">
                                            <a href="?edit=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline" title="Edit">
                                                <i data-lucide="edit" width="14" height="14"></i>
                                            </a>
                                            <a href="?toggle=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline" title="<?php echo $item['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>" onclick="return confirm('Ubah status foto ini?')">
                                                <i data-lucide="<?php echo $item['is_active'] ? 'eye-off' : 'eye'; ?>" width="14" height="14"></i>
                                            </a>
                                            <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline" style="color: var(--status-maintenance);" title="Hapus" onclick="return confirm('Hapus foto ini dari galeri?')">
                                                <i data-lucide="trash-2" width="14" height="14"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Form -->
                    <div>
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i data-lucide="<?php echo $editGallery ? 'edit' : 'plus'; ?>" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                    <?php echo $editGallery ? 'Edit Foto' : 'Tambah Foto'; ?>
                                </h4>
                            </div>
                            <div class="card-body">
                                <?php if ($editGallery): ?>
                                <div class="alert alert-info mb-3" style="padding: var(--space-sm) var(--space-md); font-size: 0.875rem;">
                                    <i data-lucide="info" width="16" height="16"></i>
                                    <span>Mode Edit</span>
                                    <a href="gallery.php" style="float: right; font-weight: 600;">Batal</a>
                                </div>
                                <?php endif; ?>

                                <form method="POST">
                                    <?php if ($editGallery): ?>
                                    <input type="hidden" name="gallery_id" value="<?php echo $editGallery['id']; ?>">
                                    <?php endif; ?>

                                    <div class="form-group">
                                        <label class="form-label">Path Gambar *</label>
                                        <input type="text" name="image_path" class="form-input" required
                                               value="<?php echo $editGallery ? htmlspecialchars($editGallery['image_path']) : ''; ?>"
                                               placeholder="assets/images/gallery/foto1.jpg">
                                        <p class="form-hint">
                                            <i data-lucide="info" width="14" height="14"></i>
                                            Path relatif dari root folder
                                        </p>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Caption</label>
                                        <textarea name="caption" class="form-textarea" rows="3" placeholder="Deskripsi foto..."><?php echo $editGallery ? htmlspecialchars($editGallery['caption']) : ''; ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Urutan Tampil</label>
                                        <input type="number" name="sort_order" class="form-input"
                                               value="<?php echo $editGallery ? $editGallery['sort_order'] : 0; ?>"
                                               min="0" placeholder="0">
                                        <p class="form-hint">Semakin kecil angka, semakin awal ditampilkan</p>
                                    </div>

                                    <?php if ($editGallery): ?>
                                    <div class="form-group">
                                        <label class="form-checkbox">
                                            <input type="checkbox" name="is_active" <?php echo $editGallery['is_active'] ? 'checked' : ''; ?>>
                                            <span>Aktif (tampilkan di website)</span>
                                        </label>
                                    </div>
                                    <?php endif; ?>

                                    <button type="submit" name="<?php echo $editGallery ? 'update_gallery' : 'add_gallery'; ?>" class="btn btn-primary btn-block">
                                        <i data-lucide="<?php echo $editGallery ? 'save' : 'plus'; ?>" width="18" height="18"></i>
                                        <?php echo $editGallery ? 'Update Foto' : 'Tambah Foto'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Info Card -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i data-lucide="help-circle" width="18" height="18" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                    Informasi
                                </h5>
                            </div>
                            <div class="card-body">
                                <ul style="padding-left: var(--space-lg); margin: 0; color: var(--gray-600); font-size: 0.875rem;">
                                    <li style="margin-bottom: var(--space-sm);">Upload foto ke folder <code>assets/images/gallery/</code></li>
                                    <li style="margin-bottom: var(--space-sm);">Format yang disarankan: JPG, PNG</li>
                                    <li style="margin-bottom: var(--space-sm);">Ukuran maksimal: 2MB</li>
                                    <li style="margin-bottom: var(--space-sm);">Resolusi optimal: 1200x800px</li>
                                    <li>Gunakan urutan untuk mengatur posisi tampilan</li>
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
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: var(--space-lg);
        }

        .gallery-item {
            background: var(--white);
            border: 2px solid var(--gray-100);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: all var(--transition-fast);
        }

        .gallery-item:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .gallery-item-inactive {
            opacity: 0.6;
        }

        .gallery-image {
            position: relative;
            width: 100%;
            height: 150px;
            overflow: hidden;
            background: var(--gray-100);
        }

        .gallery-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gallery-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--gray-400);
            gap: var(--space-sm);
        }

        .gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gallery-info {
            padding: var(--space-md);
        }

        .gallery-caption {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: var(--space-sm);
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .gallery-meta {
            display: flex;
            gap: var(--space-sm);
        }

        .gallery-actions {
            display: flex;
            gap: var(--space-xs);
            padding: var(--space-sm) var(--space-md);
            border-top: 1px solid var(--gray-100);
            background: var(--gray-50);
        }

        .gallery-actions .btn {
            flex: 1;
        }

        code {
            background: var(--primary-50);
            color: var(--primary-700);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8125rem;
            font-family: 'Courier New', monospace;
        }

        @media (max-width: 1024px) {
            .d-grid {
                grid-template-columns: 1fr !important;
            }

            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</body>
</html>
