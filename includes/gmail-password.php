<?php
/**
 * Gmail SMTP Setup - Step by Step
 * Edit this file and follow the instructions
 */

// ============================================
// IMPORTANT: Follow these steps FIRST
// ============================================
//
// Step 1: Login to Gmail
//   - Go to: gmail.com
//   - Login with: geloraminisoccer.gerung@gmail.com
//
// Step 2: Enable 2-Step Verification
//   - Go to: https://myaccount.google.com/security
//   - Find "2-Step Verification"
//   - Click "Get Started"
//   - Follow the steps (verify with phone number)
//   - MUST BE ENABLED or App Password won't work!
//
// Step 3: Generate App Password
//   - Go to: https://myaccount.google.com/apppasswords
//   - Select app: "Mail"
//   - Select device: "Other (Custom name)"
//   - Type name: "Mini Soccer Website"
//   - Click "Generate"
//   - You will get 16-digit password (format: xxxx xxxx xxxx xxxx)
//   - COPY THIS PASSWORD!
//
// Step 4: Paste App Password Below
//   - Replace YOUR_16_DIGIT_APP_PASSWORD_HERE with the password
//   - Remove spaces (or keep them, both work)
//   - Save this file
//
// Step 5: Test
//   - Create a booking on the website
//   - Check email inbox
//   - Should receive notification email!
//
// ============================================

define('GMAIL_APP_PASSWORD', 'ulom gnan zzuy tkir');

// Example (NOT REAL PASSWORD):
// define('GMAIL_APP_PASSWORD', 'abcd efgh ijkl mnop');
// Or without spaces:
// define('GMAIL_APP_PASSWORD', 'abcdefghijklmnop');

// ============================================
// DO NOT EDIT BELOW THIS LINE
// ============================================

// Check if password is set
if (GMAIL_APP_PASSWORD === 'xlxqfkvxqkheniwc') {
    echo "<h2 style='color: red;'>Gmail App Password NOT Configured!</h2>";
    echo "<p>Please follow the instructions at the top of this file:</p>";
    echo "<code>includes/gmail-password.php</code>";
    echo "<ol>";
    echo "<li>Enable 2-Step Verification in Gmail</li>";
    echo "<li>Generate App Password at: <a href='https://myaccount.google.com/apppasswords' target='_blank'>https://myaccount.google.com/apppasswords</a></li>";
    echo "<li>Copy the 16-digit password</li>";
    echo "<li>Edit this file and paste the password</li>";
    echo "<li>Save and test!</li>";
    echo "</ol>";
} else {
    echo "<h2 style='color: green;'>Gmail App Password Configured!</h2>";
    echo "<p>Password: " . substr(GMAIL_APP_PASSWORD, 0, 4) . " **** **** **** (hidden for security)</p>";
    echo "<p>Email notifications should now work!</p>";
    echo "<p><a href='booking.php'>Test by creating a booking</a></p>";
}