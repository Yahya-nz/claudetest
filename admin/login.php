<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Mohon isi username dan password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_username'] = $admin['username'];
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - <?php echo getSetting('site_name'); ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <img src="../assets/images/logo.png" alt="Gelora Gerung">
                </div>
                <h1 class="login-title">Admin Panel</h1>
                <p class="login-subtitle">Minisoccer Gelora Gerung</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                <div class="alert alert-error mb-3">
                    <i data-lucide="alert-circle" width="18" height="18"></i>
                    <span><?php echo $error; ?></span>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <div style="position: relative;">
                            <i data-lucide="user" width="18" height="18" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400);"></i>
                            <input type="text" name="username" class="form-input" style="padding-left: 44px;" placeholder="Masukkan username" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div style="position: relative;">
                            <i data-lucide="lock" width="18" height="18" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--gray-400);"></i>
                            <input type="password" name="password" id="password" class="form-input" style="padding-left: 44px;" placeholder="Masukkan password" required>
                            <button type="button" id="togglePassword" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--gray-400);">
                                <i data-lucide="eye" width="18" height="18"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i data-lucide="log-in" width="20" height="20"></i>
                        Login
                    </button>
                </form>
                
                <div class="login-footer">
                    <a href="../index.php">Kembali ke Website</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Change icon
            this.innerHTML = type === 'password' 
                ? '<i data-lucide="eye" width="18" height="18"></i>'
                : '<i data-lucide="eye-off" width="18" height="18"></i>';
            lucide.createIcons();
        });
    </script>
</body>
</html>
