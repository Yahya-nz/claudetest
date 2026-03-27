<?php
/**
 * Check Fonnte API Configuration Status
 * Run: php check-fonnte-status.php
 * Or browse: http://localhost/minisoccer/check-fonnte-status.php
 */

require_once 'includes/config.php';

$db = getDB();

echo "<h2>Fonnte WhatsApp API - Status Check</h2>";
echo "<pre>";
echo "========================================\n";
echo "CHECKING CONFIGURATION\n";
echo "========================================\n\n";

// Check WhatsApp API URL
$apiUrlStmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'whatsapp_api_url'");
$apiUrlStmt->execute();
$apiUrl = $apiUrlStmt->fetchColumn();

echo "API URL: ";
if (empty($apiUrl)) {
    echo "NOT CONFIGURED ❌\n";
    echo "  → Please set in: Admin Panel → Pengaturan → WhatsApp API\n";
    echo "  → Value should be: https://api.fonnte.com/send\n";
} else {
    echo "CONFIGURED ✓\n";
    echo "  → Value: {$apiUrl}\n";
}

echo "\n";

// Check WhatsApp API Key
$apiKeyStmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'whatsapp_api_key'");
$apiKeyStmt->execute();
$apiKey = $apiKeyStmt->fetchColumn();

echo "API Key: ";
if (empty($apiKey)) {
    echo "NOT CONFIGURED ❌\n";
    echo "  → Please set in: Admin Panel → Pengaturan → WhatsApp API\n";
    echo "  → Get from Fonnte Dashboard: https://api.fonnte.com/dashboard\n";
} else {
    echo "CONFIGURED ✓\n";
    echo "  → Value: " . substr($apiKey, 0, 10) . "... (hidden for security)\n";
}

echo "\n";
echo "========================================\n";
echo "OVERALL STATUS\n";
echo "========================================\n\n";

if (empty($apiUrl) || empty($apiKey)) {
    echo "Status: MANUAL MODE (wa.me links) ⚠️\n\n";
    echo "WhatsApp notifications will use manual links.\n";
    echo "Customer needs to click link to send message.\n\n";
    echo "TO ENABLE AUTO-SEND:\n";
    echo "1. Login to Admin Panel\n";
    echo "2. Go to Pengaturan → WhatsApp API (Opsional)\n";
    echo "3. Fill in:\n";
    echo "   - API URL: https://api.fonnte.com/send\n";
    echo "   - API Key: (your Fonnte token)\n";
    echo "4. Click Simpan Pengaturan\n";
    echo "5. Test again!\n";
} else {
    echo "Status: AUTO-SEND MODE (via Fonnte API) ✓\n\n";
    echo "WhatsApp notifications will be sent automatically!\n\n";
    echo "Test by creating a booking and check your WhatsApp.\n";
}

echo "\n";
echo "========================================\n";
echo "ADMIN CONTACT INFO\n";
echo "========================================\n\n";

$adminPhone = getSetting('site_whatsapp');
$adminEmail = getSetting('site_email');

echo "WhatsApp: {$adminPhone}\n";
echo "Email: {$adminEmail}\n";

echo "\n";
echo "========================================\n";

if (!empty($apiUrl) && !empty($apiKey)) {
    echo "\n";
    echo "Want to test the connection?\n";
    echo "Create a test booking and check:\n";
    echo "1. Customer WhatsApp receives message\n";
    echo "2. Admin WhatsApp ({$adminPhone}) receives notification\n";
    echo "\n";
}

echo "</pre>";
