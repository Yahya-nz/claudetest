<?php

        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;
/**
 * Notification Helper Functions
 * Handles Email and WhatsApp notifications for booking events
 * Version: Clean (No Emojis)
 */

/**
 * Send Email Notification
 *
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email message (HTML)
 * @return bool Success status
 */
function sendEmailNotification($to, $subject, $message) {
    // Try to use PHPMailer if available
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';

        // Load Gmail App Password
        if (file_exists(__DIR__ . '/gmail-password.php')) {
            require_once __DIR__ . '/gmail-password.php';
        }


        $mail = new PHPMailer(true);

        try {
            // Get password from gmail-password.php or empty
            $gmailPassword = defined('GMAIL_APP_PASSWORD') && GMAIL_APP_PASSWORD !== 'xlxqfkvxqkheniwc'
                ? str_replace(' ', '', GMAIL_APP_PASSWORD) // Remove spaces
                : '';

            // Check if password is set
            if (empty($gmailPassword)) {
                // SMTP not configured, return false silently
                error_log('Email not sent: Gmail App Password not configured in includes/gmail-password.php');
                return false;
            }

            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'aunymuser@gmail.com';
            $mail->Password = $gmailPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('aunymuser@gmail.com', getSetting('site_name'));
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Email error: ' . $mail->ErrorInfo);
            return false;
        }
    }

    // Fallback to basic mail() function if PHPMailer not available
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . getSetting('site_name') . ' <' . getSetting('site_email') . '>',
        'Reply-To: ' . getSetting('site_email'),
        'X-Mailer: PHP/' . phpversion()
    ];

    $result = @mail($to, $subject, $message, implode("\r\n", $headers));
    return $result;
}

/**
 * Send WhatsApp Notification via API
 *
 * @param string $phone Phone number (format: 628xxx)
 * @param string $message Message text
 * @return array Result with success status and message
 */
function sendWhatsAppNotification($phone, $message) {
    // Get WhatsApp API settings from database
    $db = getDB();
    $apiKey = getSetting('whatsapp_api_key');
    $apiUrl = getSetting('whatsapp_api_url');

    // If API not configured, return manual WhatsApp link
    if (empty($apiKey) || empty($apiUrl)) {
        $whatsappNumber = getSetting('site_whatsapp');
        $encodedMessage = urlencode($message);
        $whatsappLink = "https://wa.me/{$whatsappNumber}?text={$encodedMessage}";

        return [
            'success' => false,
            'manual' => true,
            'link' => $whatsappLink,
            'message' => 'WhatsApp API not configured. Use manual link instead.'
        ];
    }

    // Format phone number (remove leading 0, add 62)
    $phone = preg_replace('/^0/', '62', $phone);
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Prepare API request (Example for Fonnte.com API)
    $data = [
        'target' => $phone,
        'message' => $message,
        'countryCode' => '62'
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $apiKey,
            'Content-Type: application/json'
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return [
            'success' => false,
            'message' => 'Error: ' . $err
        ];
    }

    $result = json_decode($response, true);

    return [
        'success' => isset($result['status']) && $result['status'] == true,
        'message' => $result['message'] ?? 'WhatsApp sent',
        'data' => $result
    ];
}

/**
 * Get Email Template for Booking Notification
 */
function getBookingEmailTemplate($bookingData, $type = 'created') {
    $siteName = getSetting('site_name');
    $siteEmail = getSetting('site_email');
    $sitePhone = getSetting('site_phone');
    $siteWhatsapp = getSetting('site_whatsapp');

    $customerName = htmlspecialchars($bookingData['customer_name']);
    $teamName = htmlspecialchars($bookingData['team_name']);
    $bookingCode = htmlspecialchars($bookingData['booking_code']);
    $bookingDate = date('d F Y', strtotime($bookingData['booking_date']));
    $timeSlot = htmlspecialchars($bookingData['time_slot']);
    $totalPrice = formatRupiah($bookingData['total_price']);

    // DP-specific data
    $paymentType = $bookingData['payment_type'] ?? 'full';
    $paidAmount = $bookingData['paid_amount'] ?? 0;
    $dpDeadline = $bookingData['dp_deadline'] ?? null;
    $dpPercentage = getSetting('dp_percentage') ?? 50;
    $remainingAmount = $bookingData['total_price'] - $paidAmount;

    // Determine if this is a DP payment
    $isDP = $paymentType === 'dp';

    $statusMessages = [
        'created' => [
            'title' => 'Booking Berhasil Dibuat!',
            'heading' => 'Terima kasih telah melakukan booking',
            'status' => $isDP ? 'PENDING - Menunggu Pembayaran DP' : 'PENDING - Menunggu Pembayaran',
            'statusColor' => '#f59e0b',
            'message' => $isDP
                ? 'Silakan lakukan pembayaran DP minimal <strong>Rp 100.000</strong> untuk mengkonfirmasi booking Anda.'
                : 'Silakan lakukan pembayaran minimal <strong>Rp 100.000</strong> untuk mengkonfirmasi booking Anda.',
            'action' => 'Segera lakukan pembayaran DP untuk mengkonfirmasi booking Anda.'
        ],
        'confirmed' => [
            'title' => $isDP ? 'DP Diterima!' : 'Pembayaran Diterima!',
            'heading' => $isDP ? 'DP Anda telah diterima' : 'Booking Anda telah dikonfirmasi',
            'status' => $isDP ? 'CONFIRMED - DP Diterima' : 'CONFIRMED - Pembayaran Diterima',
            'statusColor' => '#10b981',
            'message' => $isDP
                ? 'Pembayaran DP Anda telah kami terima. Booking telah dikonfirmasi. <strong>Jangan lupa lunasi sisa pembayaran sebelum deadline!</strong>'
                : 'Pembayaran Anda telah kami terima dan booking telah dikonfirmasi.',
            'action' => $isDP
                ? 'Sisa pembayaran harus dilunasi sebelum ' . ($dpDeadline ? date('d F Y', strtotime($dpDeadline)) : 'hari main') . '.'
                : 'Silakan datang sesuai jadwal yang telah ditentukan. Terima kasih!'
        ],
        'completed' => [
            'title' => 'Booking Selesai',
            'heading' => 'Terima kasih telah menggunakan layanan kami',
            'status' => 'COMPLETED',
            'statusColor' => '#6366f1',
            'message' => 'Booking Anda telah selesai. Kami harap Anda puas dengan layanan kami.',
            'action' => 'Jangan ragu untuk booking lagi di lain waktu!'
        ]
    ];

    $msg = $statusMessages[$type];

    // Build payment info section
    $paymentInfoHtml = '';
    if ($type === 'created') {
        if ($isDP) {
            $paymentInfoHtml = "
                <div style='background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin-top: 20px; border-radius: 4px;'>
                    <p style='margin: 0; color: #92400e; font-size: 14px;'><strong>PENTING:</strong> {$msg['action']}</p>
                </div>

                <!-- DP Payment Summary -->
                <div style='margin-top: 20px; padding: 20px; background-color: #fef3c7; border-radius: 6px; border: 2px solid #f59e0b;'>
                    <h3 style='margin: 0 0 15px 0; color: #92400e; font-size: 16px;'>Pembayaran DP ({$dpPercentage}%):</h3>
                    <table width='100%' cellpadding='6' cellspacing='0' style='border: 1px solid #fcd34d; border-radius: 4px; background-color: white;'>
                        <tr>
                            <td style='padding: 10px; border-bottom: 1px solid #fcd34d; color: #92400e; width: 35%;'><strong>Bayar Sekarang (DP):</strong></td>
                            <td style='padding: 10px; border-bottom: 1px solid #fcd34d; color: #d97706; font-size: 18px; font-weight: bold;'>" . formatRupiah($paidAmount) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border-bottom: 1px solid #fcd34d; color: #92400e;'><strong>Sisa Pembayaran:</strong></td>
                            <td style='padding: 10px; border-bottom: 1px solid #fcd34d; color: #dc2626; font-weight: bold;'>" . formatRupiah($remainingAmount) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; color: #92400e;'><strong>Deadline Pelunasan:</strong></td>
                            <td style='padding: 10px; color: #dc2626; font-weight: bold;'>" . ($dpDeadline ? date('d F Y', strtotime($dpDeadline)) : 'Sebelum hari main') . "</td>
                        </tr>
                    </table>
                </div>

                <!-- Bank Account Info -->
                <div style='margin-top: 20px; padding: 20px; background-color: #eff6ff; border-radius: 6px; border: 2px solid #3b82f6;'>
                    <h3 style='margin: 0 0 15px 0; color: #1e40af; font-size: 16px;'>Transfer DP ke Rekening:</h3>
                    <table width='100%' cellpadding='6' cellspacing='0' style='border: 1px solid #bfdbfe; border-radius: 4px; background-color: white;'>
                        <tr>
                            <td style='padding: 10px; border-bottom: 1px solid #bfdbfe; color: #1e40af; width: 35%;'><strong>Bank:</strong></td>
                            <td style='padding: 10px; border-bottom: 1px solid #bfdbfe; color: #1f2937; font-weight: bold;'>" . getSetting('bank_name') . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border-bottom: 1px solid #bfdbfe; color: #1e40af;'><strong>No. Rekening:</strong></td>
                            <td style='padding: 10px; border-bottom: 1px solid #bfdbfe; color: #1f2937; font-size: 18px; font-weight: bold; font-family: monospace;'>" . getSetting('bank_account_number') . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; color: #1e40af;'><strong>Atas Nama:</strong></td>
                            <td style='padding: 10px; color: #1f2937; font-weight: bold;'>" . getSetting('bank_account_name') . "</td>
                        </tr>
                    </table>
                    <p style='margin: 15px 0 0 0; color: #1e40af; font-size: 13px;'>
                        <strong>Cara Pembayaran DP:</strong><br>
                        1. Transfer DP sejumlah <strong>" . formatRupiah($paidAmount) . "</strong> ke rekening di atas<br>
                        2. Simpan bukti transfer<br>
                        3. Kirim bukti transfer via WhatsApp/Email dengan kode booking: <strong>{$bookingCode}</strong><br>
                        4. Kami akan konfirmasi DP Anda<br>
                        5. Lunasi sisa <strong>" . formatRupiah($remainingAmount) . "</strong> sebelum " . ($dpDeadline ? date('d F Y', strtotime($dpDeadline)) : 'hari main') . "
                    </p>
                </div>
            ";
        } else {
            $paymentInfoHtml = "
                <div style='background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin-top: 20px; border-radius: 4px;'>
                    <p style='margin: 0; color: #92400e; font-size: 14px;'><strong>PENTING:</strong> {$msg['action']}</p>
                </div>

                <!-- Bank Account Info -->
                <div style='margin-top: 20px; padding: 20px; background-color: #eff6ff; border-radius: 6px; border: 2px solid #3b82f6;'>
                    <h3 style='margin: 0 0 15px 0; color: #1e40af; font-size: 16px;'>Transfer ke Rekening:</h3>
                    <table width='100%' cellpadding='6' cellspacing='0' style='border: 1px solid #bfdbfe; border-radius: 4px; background-color: white;'>
                        <tr>
                            <td style='padding: 10px; border-bottom: 1px solid #bfdbfe; color: #1e40af; width: 35%;'><strong>Bank:</strong></td>
                            <td style='padding: 10px; border-bottom: 1px solid #bfdbfe; color: #1f2937; font-weight: bold;'>" . getSetting('bank_name') . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border-bottom: 1px solid #bfdbfe; color: #1e40af;'><strong>No. Rekening:</strong></td>
                            <td style='padding: 10px; border-bottom: 1px solid #bfdbfe; color: #1f2937; font-size: 18px; font-weight: bold; font-family: monospace;'>" . getSetting('bank_account_number') . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; color: #1e40af;'><strong>Atas Nama:</strong></td>
                            <td style='padding: 10px; color: #1f2937; font-weight: bold;'>" . getSetting('bank_account_name') . "</td>
                        </tr>
                    </table>
                    <p style='margin: 15px 0 0 0; color: #1e40af; font-size: 13px;'>
                        <strong>Cara Pembayaran:</strong><br>
                        1. Transfer sejumlah <strong>{$totalPrice}</strong> ke rekening di atas<br>
                        2. Simpan bukti transfer<br>
                        3. Kirim bukti transfer via WhatsApp/Email dengan kode booking: <strong>{$bookingCode}</strong><br>
                        4. Kami akan konfirmasi dalam beberapa menit
                    </p>
                </div>
            ";
        }
    } elseif ($type === 'confirmed' && $isDP && $remainingAmount > 0) {
        // Show remaining payment info for confirmed DP
        $paymentInfoHtml = "
            <div style='background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin-top: 20px; border-radius: 4px;'>
                <p style='margin: 0 0 10px 0; color: #92400e; font-size: 14px;'><strong>SISA PEMBAYARAN:</strong></p>
                <p style='margin: 0; color: #92400e; font-size: 16px;'>
                    Jumlah: <strong>" . formatRupiah($remainingAmount) . "</strong><br>
                    Deadline: <strong>" . ($dpDeadline ? date('d F Y', strtotime($dpDeadline)) : 'Sebelum hari main') . "</strong>
                </p>
            </div>

            <div style='margin-top: 20px; padding: 20px; background-color: #eff6ff; border-radius: 6px; border: 2px solid #3b82f6;'>
                <h3 style='margin: 0 0 15px 0; color: #1e40af; font-size: 16px;'>Transfer Pelunasan ke:</h3>
                <table width='100%' cellpadding='6' cellspacing='0' style='border: 1px solid #bfdbfe; border-radius: 4px; background-color: white;'>
                    <tr>
                        <td style='padding: 10px; border-bottom: 1px solid #bfdbfe; color: #1e40af; width: 35%;'><strong>Bank:</strong></td>
                        <td style='padding: 10px; border-bottom: 1px solid #bfdbfe; color: #1f2937; font-weight: bold;'>" . getSetting('bank_name') . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; border-bottom: 1px solid #bfdbfe; color: #1e40af;'><strong>No. Rekening:</strong></td>
                        <td style='padding: 10px; border-bottom: 1px solid #bfdbfe; color: #1f2937; font-size: 18px; font-weight: bold; font-family: monospace;'>" . getSetting('bank_account_number') . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; color: #1e40af;'><strong>Atas Nama:</strong></td>
                        <td style='padding: 10px; color: #1f2937; font-weight: bold;'>" . getSetting('bank_account_name') . "</td>
                    </tr>
                </table>
            </div>
        ";
    }

    // Build payment type row for table
    $paymentTypeRow = '';
    if ($isDP) {
        $paymentTypeRow = "
            <tr>
                <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280;'><strong>Tipe Pembayaran:</strong></td>
                <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #d97706; font-weight: bold;'>DP ({$dpPercentage}%)</td>
            </tr>
        ";
    }

    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>{$msg['title']}</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f3f4f6;'>
        <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f3f4f6; padding: 20px 0;'>
            <tr>
                <td align='center'>
                    <table width='600' cellpadding='0' cellspacing='0' style='background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                        <!-- Header -->
                        <tr>
                            <td style='background: linear-gradient(135deg, #10b981, #059669); padding: 40px 30px; text-align: center;'>
                                <h1 style='color: #ffffff; margin: 0; font-size: 28px;'>{$siteName}</h1>
                                <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 14px;'>Mini Soccer Booking System</p>
                            </td>
                        </tr>

                        <!-- Content -->
                        <tr>
                            <td style='padding: 40px 30px;'>
                                <h2 style='color: #1f2937; margin: 0 0 10px 0; font-size: 24px;'>{$msg['heading']}</h2>
                                <p style='color: #6b7280; margin: 0 0 30px 0; font-size: 14px;'>{$msg['message']}</p>

                                <!-- Booking Status -->
                                <div style='background-color: {$msg['statusColor']}; color: white; padding: 12px 20px; border-radius: 6px; text-align: center; font-weight: bold; margin-bottom: 30px;'>
                                    {$msg['status']}
                                </div>

                                <!-- Booking Details -->
                                <table width='100%' cellpadding='8' cellspacing='0' style='border: 1px solid #e5e7eb; border-radius: 6px;'>
                                    <tr style='background-color: #f9fafb;'>
                                        <td colspan='2' style='padding: 15px; border-bottom: 2px solid #e5e7eb;'>
                                            <strong style='color: #1f2937; font-size: 16px;'>Detail Booking</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280; width: 40%;'><strong>Kode Booking:</strong></td>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #1f2937; font-weight: bold;'>{$bookingCode}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280;'><strong>Nama:</strong></td>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #1f2937;'>{$customerName}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280;'><strong>Nama Tim:</strong></td>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #1f2937;'>{$teamName}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280;'><strong>Tanggal:</strong></td>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #1f2937;'>{$bookingDate}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280;'><strong>Jam:</strong></td>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #1f2937;'>{$timeSlot}</td>
                                    </tr>
                                    {$paymentTypeRow}
                                    <tr style='background-color: #f9fafb;'>
                                        <td style='padding: 15px; color: #1f2937;'><strong>Total Pembayaran:</strong></td>
                                        <td style='padding: 15px; color: #10b981; font-size: 18px; font-weight: bold;'>{$totalPrice}</td>
                                    </tr>
                                </table>

                                {$paymentInfoHtml}

                                <!-- Action Note -->
                                <div style='margin-top: 20px; padding: 15px; background-color: #f0fdf4; border-radius: 6px;'>
                                    <p style='margin: 0; color: #166534; font-size: 14px;'>{$msg['action']}</p>
                                </div>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td style='background-color: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;'>
                                <p style='margin: 0 0 10px 0; color: #6b7280; font-size: 14px;'><strong>Butuh bantuan?</strong></p>
                                <p style='margin: 0; color: #6b7280; font-size: 13px;'>
                                    WhatsApp: <a href='https://wa.me/{$siteWhatsapp}' style='color: #10b981; text-decoration: none;'>{$sitePhone}</a><br>
                                    Email: <a href='mailto:{$siteEmail}' style='color: #10b981; text-decoration: none;'>{$siteEmail}</a>
                                </p>
                                <p style='margin: 20px 0 0 0; color: #9ca3af; font-size: 12px;'>
                                    © " . date('Y') . " {$siteName}. All rights reserved.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ";

    return $html;
}

/**
 * Get WhatsApp Message Template
 */
function getBookingWhatsAppMessage($bookingData, $type = 'created') {
    $siteName = getSetting('site_name');
    $customerName = $bookingData['customer_name'];
    $teamName = $bookingData['team_name'];
    $bookingCode = $bookingData['booking_code'];
    $bookingDate = date('d F Y', strtotime($bookingData['booking_date']));
    $timeSlot = $bookingData['time_slot'];
    $totalPrice = formatRupiah($bookingData['total_price']);

    $bankName = getSetting('bank_name');
    $bankAccount = getSetting('bank_account_number');
    $bankAccountName = getSetting('bank_account_name');

    // DP-specific data
    $paymentType = $bookingData['payment_type'] ?? 'full';
    $paidAmount = $bookingData['paid_amount'] ?? 0;
    $dpDeadline = $bookingData['dp_deadline'] ?? null;
    $dpPercentage = getSetting('dp_percentage') ?? 50;
    $remainingAmount = $bookingData['total_price'] - $paidAmount;

    $isDP = $paymentType === 'dp';

    if ($isDP) {
        $messages = [
            'created' => "
*BOOKING BERHASIL DIBUAT! (DP)*

Halo {$customerName},
Terima kasih telah melakukan booking di {$siteName}!

*DETAIL BOOKING*
━━━━━━━━━━━━━━━━
Kode Booking: *{$bookingCode}*
Nama: {$customerName}
Tim: {$teamName}
Tanggal: {$bookingDate}
Jam: {$timeSlot}
Total: *{$totalPrice}*
Tipe Bayar: *DP {$dpPercentage}%*

*STATUS: PENDING*
Silakan bayar DP minimal *Rp 100.000*!

*PEMBAYARAN DP:*
━━━━━━━━━━━━━━━━
Bayar Sekarang: *" . formatRupiah($paidAmount) . "*
Sisa: *" . formatRupiah($remainingAmount) . "*
Deadline Lunas: *" . ($dpDeadline ? date('d M Y', strtotime($dpDeadline)) : 'Sebelum hari main') . "*

*TRANSFER DP KE:*
━━━━━━━━━━━━━━━━
Bank: *{$bankName}*
No. Rek: *{$bankAccount}*
A/N: *{$bankAccountName}*

*Cara Bayar DP:*
1. Transfer DP *" . formatRupiah($paidAmount) . "*
2. Simpan bukti transfer
3. Kirim bukti ke WA ini dengan kode: *{$bookingCode}*
4. Tunggu konfirmasi DP dari kami
5. Lunasi sisa *" . formatRupiah($remainingAmount) . "* sebelum deadline

Terima kasih!
            ",
            'confirmed' => "
*DP DITERIMA!*

Halo {$customerName},
Pembayaran DP Anda telah kami terima!

*DETAIL BOOKING*
━━━━━━━━━━━━━━━━
Kode Booking: *{$bookingCode}*
Nama: {$customerName}
Tim: {$teamName}
Tanggal: {$bookingDate}
Jam: {$timeSlot}
Total: *{$totalPrice}*

*STATUS: CONFIRMED (DP)*
Booking Anda telah dikonfirmasi!

*SISA PEMBAYARAN:*
━━━━━━━━━━━━━━━━
Jumlah: *" . formatRupiah($remainingAmount) . "*
Deadline: *" . ($dpDeadline ? date('d M Y', strtotime($dpDeadline)) : 'Sebelum hari main') . "*

*Transfer sisa ke:*
Bank: *{$bankName}*
No. Rek: *{$bankAccount}*
A/N: *{$bankAccountName}*

*PENTING:* Jangan lupa lunasi sebelum deadline ya!

Terima kasih!
            ",
            'completed' => "
*BOOKING SELESAI*

Halo {$customerName},
Terima kasih telah menggunakan layanan {$siteName}!

*DETAIL BOOKING*
━━━━━━━━━━━━━━━━
Kode Booking: *{$bookingCode}*
Tim: {$teamName}
Tanggal: {$bookingDate}

Kami harap Anda puas dengan layanan kami.
Jangan ragu untuk booking lagi di lain waktu!

Terima kasih!
            "
        ];
    } else {
        $messages = [
            'created' => "
*BOOKING BERHASIL DIBUAT!*

Halo {$customerName},
Terima kasih telah melakukan booking di {$siteName}!

*DETAIL BOOKING*
━━━━━━━━━━━━━━━━
Kode Booking: *{$bookingCode}*
Nama: {$customerName}
Tim: {$teamName}
Tanggal: {$bookingDate}
Jam: {$timeSlot}
Total: *{$totalPrice}*

*STATUS: PENDING*
Silakan lakukan pembayaran minimal *Rp 100.000*!

*TRANSFER KE:*
━━━━━━━━━━━━━━━━
Bank: *{$bankName}*
No. Rek: *{$bankAccount}*
A/N: *{$bankAccountName}*

*Cara Bayar:*
1. Transfer sejumlah *{$totalPrice}*
2. Simpan bukti transfer
3. Kirim bukti ke WA ini dengan kode: *{$bookingCode}*
4. Tunggu konfirmasi dari kami

Terima kasih!
            ",
            'confirmed' => "
*PEMBAYARAN DITERIMA!*

Halo {$customerName},
Pembayaran Anda telah kami terima!

*DETAIL BOOKING*
━━━━━━━━━━━━━━━━
Kode Booking: *{$bookingCode}*
Nama: {$customerName}
Tim: {$teamName}
Tanggal: {$bookingDate}
Jam: {$timeSlot}
Total: *{$totalPrice}*

*STATUS: CONFIRMED*
Booking Anda telah dikonfirmasi!

Silakan datang sesuai jadwal yang telah ditentukan.
Sampai jumpa!
            ",
            'completed' => "
*BOOKING SELESAI*

Halo {$customerName},
Terima kasih telah menggunakan layanan {$siteName}!

*DETAIL BOOKING*
━━━━━━━━━━━━━━━━
Kode Booking: *{$bookingCode}*
Tim: {$teamName}
Tanggal: {$bookingDate}

Kami harap Anda puas dengan layanan kami.
Jangan ragu untuk booking lagi di lain waktu!

Terima kasih!
            "
        ];
    }

    return trim($messages[$type]);
}

/**
 * Get Admin Email Template for New Booking
 */
function getAdminEmailTemplate($bookingData) {
    $siteName = getSetting('site_name');
    $customerName = htmlspecialchars($bookingData['customer_name']);
    $customerPhone = htmlspecialchars($bookingData['customer_phone']);
    $customerEmail = htmlspecialchars($bookingData['customer_email']);
    $teamName = htmlspecialchars($bookingData['team_name']);
    $bookingCode = htmlspecialchars($bookingData['booking_code']);
    $bookingDate = date('d F Y', strtotime($bookingData['booking_date']));
    $timeSlot = htmlspecialchars($bookingData['time_slot']);
    $totalPrice = formatRupiah($bookingData['total_price']);

    // DP-specific data
    $paymentType = $bookingData['payment_type'] ?? 'full';
    $paidAmount = $bookingData['paid_amount'] ?? 0;
    $dpDeadline = $bookingData['dp_deadline'] ?? null;
    $dpPercentage = getSetting('dp_percentage') ?? 50;
    $remainingAmount = $bookingData['total_price'] - $paidAmount;

    $isDP = $paymentType === 'dp';

    // Build payment type row
    $paymentTypeRow = '';
    $dpInfoSection = '';
    $actionSteps = '';

    if ($isDP) {
        $paymentTypeRow = "
            <tr>
                <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280;'><strong>Tipe Bayar:</strong></td>
                <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #d97706; font-weight: bold;'>DP ({$dpPercentage}%)</td>
            </tr>
            <tr>
                <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280;'><strong>DP yang Dibayar:</strong></td>
                <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #d97706; font-weight: bold;'>" . formatRupiah($paidAmount) . "</td>
            </tr>
            <tr>
                <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280;'><strong>Sisa Pembayaran:</strong></td>
                <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #dc2626; font-weight: bold;'>" . formatRupiah($remainingAmount) . "</td>
            </tr>
            <tr>
                <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280;'><strong>Deadline Lunas:</strong></td>
                <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #dc2626; font-weight: bold;'>" . ($dpDeadline ? date('d F Y', strtotime($dpDeadline)) : 'Sebelum hari main') . "</td>
            </tr>
        ";

        $actionSteps = "
            1. Tunggu customer transfer DP ke rekening<br>
            2. Cek mutasi rekening (jumlah DP: <strong>" . formatRupiah($paidAmount) . "</strong>)<br>
            3. Klik tombol <strong>Konfirmasi DP</strong> di Admin Panel<br>
            4. Ingatkan customer untuk melunasi sisa sebelum deadline<br>
            5. Setelah lunas, klik tombol <strong>Lunasi Sisa</strong>
        ";
    } else {
        $actionSteps = "
            1. Tunggu customer transfer ke rekening<br>
            2. Cek mutasi rekening<br>
            3. Konfirmasi booking di Admin Panel<br>
            4. Customer akan dapat notifikasi otomatis
        ";
    }

    $statusText = $isDP ? 'MENUNGGU PEMBAYARAN DP' : 'MENUNGGU KONFIRMASI PEMBAYARAN';

    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Booking Baru!</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f3f4f6;'>
        <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f3f4f6; padding: 20px 0;'>
            <tr>
                <td align='center'>
                    <table width='600' cellpadding='0' cellspacing='0' style='background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                        <tr>
                            <td style='background: linear-gradient(135deg, #ef4444, #dc2626); padding: 40px 30px; text-align: center;'>
                                <h1 style='color: #ffffff; margin: 0; font-size: 28px;'>BOOKING BARU!" . ($isDP ? ' (DP)' : '') . "</h1>
                                <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 14px;'>{$siteName} - Admin Panel</p>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 40px 30px;'>
                                <h2 style='color: #1f2937; margin: 0 0 10px 0; font-size: 24px;'>Ada customer baru booking!</h2>
                                <p style='color: #6b7280; margin: 0 0 30px 0; font-size: 14px;'>Segera cek dan proses pembayarannya.</p>

                                <div style='background-color: #fef3c7; color: #92400e; padding: 12px 20px; border-radius: 6px; text-align: center; font-weight: bold; margin-bottom: 30px; border-left: 4px solid #f59e0b;'>
                                    {$statusText}
                                </div>

                                <table width='100%' cellpadding='8' cellspacing='0' style='border: 1px solid #e5e7eb; border-radius: 6px;'>
                                    <tr style='background-color: #f9fafb;'>
                                        <td colspan='2' style='padding: 15px; border-bottom: 2px solid #e5e7eb;'>
                                            <strong style='color: #1f2937; font-size: 16px;'>Detail Booking</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280; width: 40%;'><strong>Kode Booking:</strong></td>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #1f2937; font-weight: bold;'>{$bookingCode}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280;'><strong>Nama Customer:</strong></td>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #1f2937;'>{$customerName}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280;'><strong>No. Telepon:</strong></td>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #1f2937;'>{$customerPhone}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280;'><strong>Email:</strong></td>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #1f2937;'>{$customerEmail}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280;'><strong>Nama Tim:</strong></td>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #1f2937;'>{$teamName}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280;'><strong>Tanggal:</strong></td>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #1f2937;'>{$bookingDate}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #6b7280;'><strong>Jam:</strong></td>
                                        <td style='padding: 12px 15px; border-bottom: 1px solid #e5e7eb; color: #1f2937;'>{$timeSlot}</td>
                                    </tr>
                                    {$paymentTypeRow}
                                    <tr style='background-color: #f9fafb;'>
                                        <td style='padding: 15px; color: #1f2937;'><strong>Total Pembayaran:</strong></td>
                                        <td style='padding: 15px; color: #10b981; font-size: 18px; font-weight: bold;'>{$totalPrice}</td>
                                    </tr>
                                </table>

                                <div style='margin-top: 30px; padding: 20px; background-color: #eff6ff; border-radius: 6px; border-left: 4px solid #3b82f6;'>
                                    <p style='margin: 0 0 10px 0; color: #1e40af; font-weight: bold;'>Action Required:</p>
                                    <p style='margin: 0; color: #1e40af; font-size: 14px;'>
                                        {$actionSteps}
                                    </p>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style='background-color: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;'>
                                <p style='margin: 0; color: #9ca3af; font-size: 12px;'>
                                    © " . date('Y') . " {$siteName}. Admin Notification System.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ";

    return $html;
}

/**
 * Get Admin WhatsApp Message for New Booking
 */
function getAdminWhatsAppMessage($bookingData) {
    $siteName = getSetting('site_name');
    $customerName = $bookingData['customer_name'];
    $customerPhone = $bookingData['customer_phone'];
    $teamName = $bookingData['team_name'];
    $bookingCode = $bookingData['booking_code'];
    $bookingDate = date('d F Y', strtotime($bookingData['booking_date']));
    $timeSlot = $bookingData['time_slot'];
    $totalPrice = formatRupiah($bookingData['total_price']);

    // DP-specific data
    $paymentType = $bookingData['payment_type'] ?? 'full';
    $paidAmount = $bookingData['paid_amount'] ?? 0;
    $dpDeadline = $bookingData['dp_deadline'] ?? null;
    $dpPercentage = getSetting('dp_percentage') ?? 50;
    $remainingAmount = $bookingData['total_price'] - $paidAmount;

    $isDP = $paymentType === 'dp';

    if ($isDP) {
        $message = "
*BOOKING BARU (DP)! - {$siteName}*

Ada customer baru booking dengan DP:

*DETAIL BOOKING*
━━━━━━━━━━━━━━━━
Kode: *{$bookingCode}*
Customer: {$customerName}
Telepon: {$customerPhone}
Tim: {$teamName}
Tanggal: {$bookingDate}
Jam: {$timeSlot}

*PEMBAYARAN DP:*
━━━━━━━━━━━━━━━━
Total: *{$totalPrice}*
Bayar DP: *" . formatRupiah($paidAmount) . "*
Sisa: *" . formatRupiah($remainingAmount) . "*
Deadline: *" . ($dpDeadline ? date('d M Y', strtotime($dpDeadline)) : 'Sebelum hari main') . "*

*STATUS: PENDING (DP)*
Menunggu pembayaran DP dari customer.

*Action Required:*
1. Tunggu customer transfer DP
2. Cek mutasi (jumlah: *" . formatRupiah($paidAmount) . "*)
3. Klik *Konfirmasi DP* di Admin Panel
4. Ingatkan customer untuk melunasi

Segera proses ya!
        ";
    } else {
        $message = "
*BOOKING BARU! - {$siteName}*

Ada customer baru melakukan booking:

*DETAIL BOOKING*
━━━━━━━━━━━━━━━━
Kode: *{$bookingCode}*
Customer: {$customerName}
Telepon: {$customerPhone}
Tim: {$teamName}
Tanggal: {$bookingDate}
Jam: {$timeSlot}
Total: *{$totalPrice}*

*STATUS: PENDING*
Menunggu pembayaran dari customer.

*Action Required:*
1. Tunggu customer transfer
2. Cek mutasi rekening
3. Konfirmasi di Admin Panel

Segera proses ya!
        ";
    }

    return trim($message);
}

/**
 * Send Admin Notification (Email + WhatsApp)
 */
function sendAdminNotification($bookingData) {
    $results = [
        'email' => false,
        'whatsapp' => false
    ];

    $adminEmail = getSetting('site_email');
    $adminPhone = getSetting('site_whatsapp');

    // Send Email to admin
    if (!empty($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $emailSubject = 'Booking Baru #' . $bookingData['booking_code'] . ' - ' . getSetting('site_name');
        $emailMessage = getAdminEmailTemplate($bookingData);
        $results['email'] = sendEmailNotification($adminEmail, $emailSubject, $emailMessage);
    }

    // Send WhatsApp to admin
    if (!empty($adminPhone)) {
        $whatsappMessage = getAdminWhatsAppMessage($bookingData);
        $results['whatsapp'] = sendWhatsAppNotification($adminPhone, $whatsappMessage);
    }

    return $results;
}

/**
 * Send Booking Notification (Email + WhatsApp)
 *
 * @param array $bookingData Booking data
 * @param string $type Notification type: created, confirmed, completed
 * @return array Results
 */
function sendBookingNotification($bookingData, $type = 'created') {
    $results = [
        'email' => false,
        'whatsapp' => false,
        'admin_email' => false,
        'admin_whatsapp' => false
    ];

    // Get customer contact info
    $customerEmail = $bookingData['customer_email'];
    $customerPhone = $bookingData['customer_phone'];

    // Send Email if email provided
    if (!empty($customerEmail) && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        $emailSubject = getSetting('site_name') . ' - ';
        if ($type === 'created') {
            $emailSubject .= 'Booking Berhasil Dibuat #' . $bookingData['booking_code'];
        } elseif ($type === 'confirmed') {
            $emailSubject .= 'Pembayaran Diterima #' . $bookingData['booking_code'];
        } else {
            $emailSubject .= 'Booking Selesai #' . $bookingData['booking_code'];
        }

        $emailMessage = getBookingEmailTemplate($bookingData, $type);
        $results['email'] = sendEmailNotification($customerEmail, $emailSubject, $emailMessage);
    }

    // Send WhatsApp if phone provided
    if (!empty($customerPhone)) {
        $whatsappMessage = getBookingWhatsAppMessage($bookingData, $type);
        $results['whatsapp'] = sendWhatsAppNotification($customerPhone, $whatsappMessage);
    }

    // Send notification to admin ONLY when new booking created
    if ($type === 'created') {
        $adminResults = sendAdminNotification($bookingData);
        $results['admin_email'] = $adminResults['email'];
        $results['admin_whatsapp'] = $adminResults['whatsapp'];
    }

    return $results;
}

/**
 * Log notification to database (optional)
 */
function logNotification($bookingId, $type, $channel, $status, $message = '') {
    $db = getDB();

    // Create notifications table if not exists
    $db->exec("
        CREATE TABLE IF NOT EXISTS notification_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            type VARCHAR(20) NOT NULL,
            channel VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL,
            message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_booking (booking_id),
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $stmt = $db->prepare("INSERT INTO notification_logs (booking_id, type, channel, status, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$bookingId, $type, $channel, $status, $message]);
}