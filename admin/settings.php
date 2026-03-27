<?php
require_once '../includes/config.php';
requireLogin();

$db = getDB();
$pageTitle = 'Pengaturan Website';

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => $_POST['site_name'] ?? '',
        'site_tagline' => $_POST['site_tagline'] ?? '',
        'site_phone' => $_POST['site_phone'] ?? '',
        'site_email' => $_POST['site_email'] ?? '',
        'site_address' => $_POST['site_address'] ?? '',
        'site_whatsapp' => $_POST['site_whatsapp'] ?? '',
        'booking_notice' => $_POST['booking_notice'] ?? '',
        'photographer_fee' => $_POST['photographer_fee'] ?? '50000',
        'tax_percent' => $_POST['tax_percent'] ?? '10',
        'whatsapp_api_url' => $_POST['whatsapp_api_url'] ?? '',
        'whatsapp_api_key' => $_POST['whatsapp_api_key'] ?? '',
        'bank_name' => $_POST['bank_name'] ?? '',
        'bank_account_number' => $_POST['bank_account_number'] ?? '',
        'bank_account_name' => $_POST['bank_account_name'] ?? '',
        'dp_enabled' => isset($_POST['dp_enabled']) ? '1' : '0',
        'dp_percentage' => $_POST['dp_percentage'] ?? '50',
        'dp_deadline_days' => $_POST['dp_deadline_days'] ?? '1'
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    
    $message = 'Pengaturan berhasil disimpan.';
    $messageType = 'success';
}

// Get all settings
$settingsStmt = $db->query("SELECT * FROM site_settings");
$settingsData = $settingsStmt->fetchAll();
$settings = [];
foreach ($settingsData as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
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
                        <h1 class="page-title">Pengaturan Website</h1>
                        <p class="page-subtitle">Kelola informasi dasar website</p>
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
                        <!-- General Settings -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i data-lucide="globe" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                    Informasi Umum
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Nama Website</label>
                                    <input type="text" name="site_name" class="form-input" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Tagline</label>
                                    <input type="text" name="site_tagline" class="form-input" value="<?php echo htmlspecialchars($settings['site_tagline'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Alamat</label>
                                    <textarea name="site_address" class="form-textarea" rows="3"><?php echo htmlspecialchars($settings['site_address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contact Settings -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i data-lucide="phone" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                    Kontak
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">No. Telepon</label>
                                    <input type="text" name="site_phone" class="form-input" value="<?php echo htmlspecialchars($settings['site_phone'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="site_email" class="form-input" value="<?php echo htmlspecialchars($settings['site_email'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">No. WhatsApp (tanpa +)</label>
                                    <input type="text" name="site_whatsapp" class="form-input" value="<?php echo htmlspecialchars($settings['site_whatsapp'] ?? ''); ?>" placeholder="6281234567890">
                                    <p class="form-hint">Format: 6281234567890 (tanpa tanda +)</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Booking Settings -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i data-lucide="calendar-check" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                    Pengaturan Booking
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Catatan Booking</label>
                                    <textarea name="booking_notice" class="form-textarea" rows="3"><?php echo htmlspecialchars($settings['booking_notice'] ?? ''); ?></textarea>
                                    <p class="form-hint">Akan ditampilkan di halaman booking</p>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Biaya Fotografer Sendiri (Rp)</label>
                                    <input type="number" name="photographer_fee" class="form-input" value="<?php echo htmlspecialchars($settings['photographer_fee'] ?? '50000'); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Pajak (%)</label>
                                    <input type="number" name="tax_percent" class="form-input" value="<?php echo htmlspecialchars($settings['tax_percent'] ?? '10'); ?>" min="0" max="100">
                                </div>
                            </div>
                        </div>

                        <!-- DP Payment Settings -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i data-lucide="wallet" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                    Pengaturan DP (Down Payment)
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-3" style="background-color: #dbeafe; border-left: 4px solid #3b82f6; padding: 12px;">
                                    <p style="margin: 0; font-size: 13px; color: #1e40af;">
                                        <strong>💡 Info:</strong> Opsi DP (Down Payment) memungkinkan customer membayar setengah harga terlebih dahulu.
                                        Sisa pembayaran harus dilunasi sebelum hari main.
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Aktifkan Opsi DP</label>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <label class="switch">
                                            <input type="checkbox" name="dp_enabled" value="1" <?php echo ($settings['dp_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                            <span class="switch-slider"></span>
                                        </label>
                                        <span style="color: var(--gray-600); font-size: 0.875rem;">
                                            <?php echo ($settings['dp_enabled'] ?? '0') === '1' ? 'Aktif - Customer dapat memilih DP' : 'Nonaktif - Hanya bayar lunas'; ?>
                                        </span>
                                    </div>
                                    <p class="form-hint" style="margin-top: 8px;">Jika diaktifkan, customer dapat memilih untuk membayar DP 50% atau bayar lunas</p>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Persentase DP (%)</label>
                                    <input type="number" name="dp_percentage" class="form-input" value="<?php echo htmlspecialchars($settings['dp_percentage'] ?? '50'); ?>" min="10" max="90">
                                    <p class="form-hint">Minimal 10%, maksimal 90%. Default: 50%</p>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Batas Pelunasan (hari sebelum main)</label>
                                    <input type="number" name="dp_deadline_days" class="form-input" value="<?php echo htmlspecialchars($settings['dp_deadline_days'] ?? '1'); ?>" min="0" max="7">
                                    <p class="form-hint">Customer harus melunasi sisa pembayaran paling lambat X hari sebelum hari main. 0 = hari H</p>
                                </div>
                            </div>
                        </div>

                        <!-- WhatsApp API Settings -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i data-lucide="message-circle" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                    WhatsApp API (Opsional)
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-3" style="background-color: #e0f2fe; border-left: 4px solid #0ea5e9; padding: 12px;">
                                    <p style="margin: 0; font-size: 13px; color: #075985;">
                                        <strong>💡 Info:</strong> Jika tidak diisi, notifikasi WhatsApp akan menggunakan link manual wa.me.
                                        Untuk auto-send, gunakan layanan seperti <a href="https://fonnte.com" target="_blank" style="color: #0ea5e9;">Fonnte.com</a>,
                                        <a href="https://wablas.com" target="_blank" style="color: #0ea5e9;">Wablas.com</a>, atau
                                        <a href="https://twilio.com" target="_blank" style="color: #0ea5e9;">Twilio</a>.
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">API URL Endpoint</label>
                                    <input type="url" name="whatsapp_api_url" class="form-input" value="<?php echo htmlspecialchars($settings['whatsapp_api_url'] ?? ''); ?>" placeholder="https://api.fonnte.com/send">
                                    <p class="form-hint">Contoh: https://api.fonnte.com/send atau https://console.wablas.com/api/send-message</p>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">API Key / Token</label>
                                    <input type="text" name="whatsapp_api_key" class="form-input" value="<?php echo htmlspecialchars($settings['whatsapp_api_key'] ?? ''); ?>" placeholder="Your-API-Key-Here">
                                    <p class="form-hint">API key dari provider WhatsApp API Anda</p>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Account Settings -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i data-lucide="credit-card" width="20" height="20" style="vertical-align: middle; margin-right: 8px; color: var(--primary-600);"></i>
                                    Rekening Bank (Payment Manual)
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-3" style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px;">
                                    <p style="margin: 0; font-size: 13px; color: #92400e;">
                                        <strong>💳 Info:</strong> Rekening ini akan ditampilkan kepada customer untuk pembayaran manual via transfer bank.
                                    </p>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Nama Bank *</label>
                                    <input type="text" name="bank_name" class="form-input" value="<?php echo htmlspecialchars($settings['bank_name'] ?? ''); ?>" placeholder="Contoh: Bank BCA, Bank Mandiri, Bank BRI" required>
                                    <p class="form-hint">Nama bank yang digunakan untuk menerima pembayaran</p>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Nomor Rekening *</label>
                                    <input type="text" name="bank_account_number" class="form-input" value="<?php echo htmlspecialchars($settings['bank_account_number'] ?? ''); ?>" placeholder="1234567890" required>
                                    <p class="form-hint">Nomor rekening tanpa spasi atau tanda hubung</p>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Atas Nama *</label>
                                    <input type="text" name="bank_account_name" class="form-input" value="<?php echo htmlspecialchars($settings['bank_account_name'] ?? ''); ?>" placeholder="Nama pemilik rekening" required>
                                    <p class="form-hint">Nama pemilik rekening sesuai buku tabungan</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i data-lucide="save" width="20" height="20"></i>
                            Simpan Pengaturan
                        </button>
                    </div>
                </form>
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