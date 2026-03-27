<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get and sanitize input
$name = sanitize($_POST['name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$subject = sanitize($_POST['subject'] ?? '');
$message = sanitize($_POST['message'] ?? '');

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Nama harus diisi';
}

if (empty($email)) {
    $errors[] = 'Email harus diisi';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Format email tidak valid';
}

if (empty($subject)) {
    $errors[] = 'Subjek harus dipilih';
}

if (empty($message)) {
    $errors[] = 'Pesan harus diisi';
} elseif (strlen($message) < 10) {
    $errors[] = 'Pesan minimal 10 karakter';
}

// If there are validation errors
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Validasi gagal',
        'errors' => $errors
    ]);
    exit;
}

try {
    $db = getDB();

    // Check if contact_messages table exists, if not create it
    $tableCheckStmt = $db->query("SHOW TABLES LIKE 'contact_messages'");
    if ($tableCheckStmt->rowCount() === 0) {
        $db->exec("
            CREATE TABLE contact_messages (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                phone VARCHAR(20),
                subject VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    // Insert contact message
    $stmt = $db->prepare("
        INSERT INTO contact_messages (name, email, phone, subject, message)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $name,
        $email,
        $phone,
        $subject,
        $message
    ]);

    // Optional: Send email notification to admin
    // You can implement email sending here using PHPMailer or similar

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Pesan Anda telah terkirim! Kami akan segera menghubungi Anda.'
    ]);

} catch (PDOException $e) {
    error_log('Contact form error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem. Silakan coba lagi atau hubungi kami via WhatsApp.'
    ]);
}
