<?php
/**
 * Test Email Notification System
 *
 * Usage:
 * 1. Generate Gmail App Password from: https://myaccount.google.com/apppasswords
 * 2. Edit includes/gmail-password.php and paste the 16-digit password
 * 3. Run this script: http://localhost/minisoccer/test-email.php
 */

require_once 'includes/config.php';
require_once 'includes/notifications.php';

// Simple HTML output
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email Notifications</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f9fafb;
        }
        h1 { color: #10b981; }
        .box {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .success {
            color: #10b981;
            background: #f0fdf4;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #10b981;
        }
        .error {
            color: #ef4444;
            background: #fef2f2;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #ef4444;
        }
        .warning {
            color: #f59e0b;
            background: #fffbeb;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #f59e0b;
        }
        .info {
            color: #1e40af;
            background: #eff6ff;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        pre {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 13px;
        }
        button {
            background: #10b981;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        button:hover { background: #059669; }
        .code {
            background: #1f2937;
            color: #f9fafb;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 13px;
        }
        input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            margin: 10px 0;
        }
        .step {
            background: #f9fafb;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 3px solid #6366f1;
        }
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: #6366f1;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <h1>Test Email Notifications</h1>

<?php
// Check Gmail App Password configuration
if (file_exists(__DIR__ . '/includes/gmail-password.php')) {
    require_once __DIR__ . '/includes/gmail-password.php';
}

$passwordConfigured = defined('GMAIL_APP_PASSWORD') && GMAIL_APP_PASSWORD !== 'YOUR_16_DIGIT_APP_PASSWORD_HERE';

if (!$passwordConfigured) {
    ?>
    <div class="box">
        <div class="error">
            <h3>Gmail App Password NOT Configured</h3>
            <p>Email notifications will not work until you complete the setup.</p>
        </div>

        <h3>Complete Setup in 3 Steps:</h3>

        <div class="step">
            <span class="step-number">1</span>
            <strong>Generate App Password</strong>
            <p style="margin: 10px 0 10px 40px;">
                You need a special password from Google (not your regular Gmail password):<br>
                - Go to: <a href="https://myaccount.google.com/apppasswords" target="_blank">https://myaccount.google.com/apppasswords</a><br>
                - App: Select "Mail"<br>
                - Device: Select "Other" and type "Mini Soccer Website"<br>
                - Click "Generate"<br>
                - Copy the 16-digit password (format: xxxx xxxx xxxx xxxx)
            </p>
        </div>

        <div class="step">
            <span class="step-number">2</span>
            <strong>Edit Configuration File</strong>
            <p style="margin: 10px 0 10px 40px;">
                Open file: <span class="code">includes/gmail-password.php</span><br><br>
                Find this line:<br>
                <pre>define('GMAIL_APP_PASSWORD', 'YOUR_16_DIGIT_APP_PASSWORD_HERE');</pre>
                Replace with your App Password:<br>
                <pre>define('GMAIL_APP_PASSWORD', 'abcd efgh ijkl mnop');</pre>
                (Use your actual password, you can keep the spaces or remove them)
            </p>
        </div>

        <div class="step">
            <span class="step-number">3</span>
            <strong>Test Email</strong>
            <p style="margin: 10px 0 10px 40px;">
                Save the file and refresh this page!<br>
                If configured correctly, you'll see a test button appear.
            </p>
        </div>

        <div class="info">
            <strong>Need Help?</strong><br>
            - Make sure 2-Step Verification is enabled in Gmail first<br>
            - The App Password is different from your regular Gmail password<br>
            - Copy the entire 16-digit code from Google (spaces don't matter)<br>
            - Save the file after editing
        </div>
    </div>
    <?php
} else {
    // Password is configured, show test form
    $adminEmail = getSetting('site_email');

    if (isset($_POST['test_email'])) {
        $testEmail = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);

        if (!$testEmail) {
            echo '<div class="box"><div class="error">Invalid email address!</div></div>';
        } else {
            echo '<div class="box">';
            echo '<h3>Sending Test Email...</h3>';
            echo '<pre>';

            $subject = 'Test Email - Mini Soccer Gerung';
            $message = getBookingConfirmationEmail([
                'booking_id' => 'TEST-' . date('YmdHis'),
                'customer_name' => 'Test Customer',
                'customer_phone' => '081234567890',
                'customer_email' => $testEmail,
                'booking_date' => date('Y-m-d', strtotime('+1 day')),
                'time_slot' => '08:00 - 09:00',
                'field_name' => 'Lapangan 1',
                'team_name' => 'Test Team',
                'total_price' => 150000,
                'status' => 'confirmed'
            ]);

            echo "To: {$testEmail}\n";
            echo "Subject: {$subject}\n\n";

            $result = sendEmailNotification($testEmail, $subject, $message);

            if ($result) {
                echo "\n<span style='color: #10b981; font-weight: bold;'>SUCCESS - Email sent!</span>\n";
                echo '</pre>';
                echo '<div class="success">';
                echo '<h4>Email Sent Successfully!</h4>';
                echo "<p>Check inbox: <strong>{$testEmail}</strong></p>";
                echo '<p>If you don\'t see it:</p>';
                echo '<ul>';
                echo '<li>Check Spam/Junk folder</li>';
                echo '<li>Wait 1-2 minutes for delivery</li>';
                echo '<li>Make sure email address is correct</li>';
                echo '</ul>';
                echo '</div>';
            } else {
                echo "\n<span style='color: #ef4444; font-weight: bold;'>FAILED - Email not sent!</span>\n";
                echo '</pre>';
                echo '<div class="error">';
                echo '<h4>Email Failed to Send</h4>';
                echo '<p>Possible causes:</p>';
                echo '<ul>';
                echo '<li>Gmail App Password is incorrect</li>';
                echo '<li>2-Step Verification not enabled in Gmail</li>';
                echo '<li>Internet connection issue</li>';
                echo '<li>Port 587 blocked by firewall</li>';
                echo '</ul>';
                echo '<p><strong>Check PHP error log for details:</strong></p>';
                echo '<pre style="font-size: 11px;">';
                if (file_exists('C:/xampp/apache/logs/error.log')) {
                    echo 'Windows: C:\xampp\apache\logs\error.log';
                } else {
                    echo 'Linux: /var/log/apache2/error.log';
                }
                echo '</pre>';
                echo '</div>';
            }
            echo '</div>';
        }
    }
    ?>
    <div class="box">
        <div class="success">
            <h3>Gmail App Password Configured!</h3>
            <p>Password: <?php echo substr(GMAIL_APP_PASSWORD, 0, 4); ?> **** **** **** (hidden for security)</p>
        </div>

        <h3>Send Test Email</h3>
        <form method="POST">
            <label for="test_email"><strong>Enter email address to receive test:</strong></label>
            <input
                type="email"
                id="test_email"
                name="test_email"
                placeholder="your-email@example.com"
                value="<?php echo htmlspecialchars($adminEmail); ?>"
                required
            >
            <button type="submit">Send Test Email</button>
        </form>

        <div class="info" style="margin-top: 20px;">
            <strong>What will be tested?</strong>
            <ul>
                <li>PHPMailer connection to Gmail SMTP</li>
                <li>App Password authentication</li>
                <li>Email template formatting</li>
                <li>Delivery to inbox</li>
            </ul>
            <p>If test succeeds, all booking notifications will work!</p>
        </div>
    </div>
    <?php
}
?>

    <div class="box">
        <h3>Current Configuration</h3>
        <pre><?php
echo "PHPMailer Installed: " . (file_exists(__DIR__ . '/vendor/autoload.php') ? "YES" : "NO") . "\n";
echo "Gmail Config File: " . (file_exists(__DIR__ . '/includes/gmail-password.php') ? "YES" : "NO") . "\n";
echo "App Password Set: " . ($passwordConfigured ? "YES" : "NO") . "\n";
echo "Admin Email: " . getSetting('site_email') . "\n";
echo "SMTP Host: smtp.gmail.com\n";
echo "SMTP Port: 587\n";
echo "SMTP Security: TLS\n";
        ?></pre>
    </div>

</body>
</html>