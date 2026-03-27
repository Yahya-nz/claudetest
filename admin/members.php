<?php
require_once '../includes/config.php';
requireLogin();

$db = getDB();
$pageTitle = 'Member';

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_member'])) {
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        $email = sanitize($_POST['email']);
        $discountPercent = (float)$_POST['discount_percent'];
        $validUntil = $_POST['valid_until'];
        $memberCode = 'MBR' . date('Ymd') . strtoupper(substr(uniqid(), -4));
        
        $stmt = $db->prepare("INSERT INTO members (member_code, name, phone, email, discount_percent, valid_until) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$memberCode, $name, $phone, $email, $discountPercent, $validUntil]);
        $message = "Member berhasil ditambahkan dengan kode: $memberCode";
        $messageType = 'success';
    }
    
    if (isset($_POST['update_member'])) {
        $id = (int)$_POST['member_id'];
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        $email = sanitize($_POST['email']);
        $discountPercent = (float)$_POST['discount_percent'];
        $validUntil = $_POST['valid_until'];
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $db->prepare("UPDATE members SET name = ?, phone = ?, email = ?, discount_percent = ?, valid_until = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $email, $discountPercent, $validUntil, $isActive, $id]);
        $message = 'Data member berhasil diperbarui.';
        $messageType = 'success';
    }
}

// Handle toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE members SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Status member berhasil diubah.';
    $messageType = 'success';
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM members WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Member berhasil dihapus.';
    $messageType = 'success';
}

// Get members
$members = $db->query("SELECT * FROM members ORDER BY created_at DESC")->fetchAll();

// Edit mode
$editMember = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$editId]);
    $editMember = $stmt->fetch();
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
                        <h1 class="page-title">Member</h1>
                        <p class="page-subtitle">Kelola data member dan diskon</p>
                    </div>
                </div>
                
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?> mb-4">
                    <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" width="20" height="20"></i>
                    <span><?php echo $message; ?></span>
                </div>
                <?php endif; ?>
                
                <div class="d-grid" style="grid-template-columns: 2fr 1fr; gap: var(--space-xl);">
                    <!-- Members List -->
                    <div class="data-table-container">
                        <div class="data-table-header">
                            <h3 class="data-table-title">Daftar Member</h3>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama</th>
                                        <th>Telepon</th>
                                        <th>Diskon</th>
                                        <th>Berlaku Sampai</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($members)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted" style="padding: var(--space-xl);">Belum ada member</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($members as $member): 
                                        $isExpired = $member['valid_until'] && strtotime($member['valid_until']) < time();
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($member['member_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($member['name']); ?></td>
                                        <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                        <td style="color: var(--primary-600); font-weight: 600;"><?php echo $member['discount_percent']; ?>%</td>
                                        <td>
                                            <?php if ($member['valid_until']): ?>
                                                <span class="<?php echo $isExpired ? 'text-danger' : ''; ?>"><?php echo date('d M Y', strtotime($member['valid_until'])); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Selamanya</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isExpired): ?>
                                                <span class="badge badge-danger">Expired</span>
                                            <?php else: ?>
                                                <span class="badge <?php echo $member['is_active'] ? 'badge-success' : 'badge-warning'; ?>">
                                                    <?php echo $member['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                <a href="?edit=<?php echo $member['id']; ?>" class="action-btn action-btn-edit" title="Edit">
                                                    <i data-lucide="edit" width="16" height="16"></i>
                                                </a>
                                                <a href="?toggle=<?php echo $member['id']; ?>" class="action-btn action-btn-view" title="Toggle Status">
                                                    <i data-lucide="<?php echo $member['is_active'] ? 'eye-off' : 'eye'; ?>" width="16" height="16"></i>
                                                </a>
                                                <a href="?delete=<?php echo $member['id']; ?>" class="action-btn action-btn-delete" title="Hapus" onclick="return confirm('Hapus member ini?')">
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
                    </div>
                    
                    <!-- Add/Edit Form -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">
                                <i data-lucide="<?php echo $editMember ? 'edit' : 'user-plus'; ?>" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                <?php echo $editMember ? 'Edit Member' : 'Tambah Member Baru'; ?>
                            </h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?php if ($editMember): ?>
                                <input type="hidden" name="member_id" value="<?php echo $editMember['id']; ?>">
                                
                                <div class="form-group">
                                    <label class="form-label">Kode Member</label>
                                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($editMember['member_code']); ?>" disabled style="background: var(--gray-100);">
                                </div>
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="name" class="form-input" required value="<?php echo $editMember ? htmlspecialchars($editMember['name']) : ''; ?>" placeholder="Nama lengkap">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">No. Telepon</label>
                                    <input type="tel" name="phone" class="form-input" required value="<?php echo $editMember ? htmlspecialchars($editMember['phone']) : ''; ?>" placeholder="08xxxxxxxxxx">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-input" value="<?php echo $editMember ? htmlspecialchars($editMember['email']) : ''; ?>" placeholder="email@example.com">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Diskon (%)</label>
                                    <input type="number" name="discount_percent" class="form-input" value="<?php echo $editMember ? $editMember['discount_percent'] : '10'; ?>" min="0" max="100" step="0.5">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Berlaku Sampai</label>
                                    <input type="date" name="valid_until" class="form-input" value="<?php echo $editMember ? $editMember['valid_until'] : ''; ?>">
                                    <p class="form-hint">Kosongkan jika berlaku selamanya</p>
                                </div>
                                
                                <?php if ($editMember): ?>
                                <div class="form-group">
                                    <label class="form-checkbox">
                                        <input type="checkbox" name="is_active" <?php echo $editMember['is_active'] ? 'checked' : ''; ?>>
                                        <span>Aktif</span>
                                    </label>
                                </div>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" name="<?php echo $editMember ? 'update_member' : 'add_member'; ?>" class="btn btn-primary flex-1">
                                        <i data-lucide="<?php echo $editMember ? 'save' : 'plus'; ?>" width="18" height="18"></i>
                                        <?php echo $editMember ? 'Simpan Perubahan' : 'Tambah Member'; ?>
                                    </button>
                                    <?php if ($editMember): ?>
                                    <a href="members.php" class="btn btn-outline">Batal</a>
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
