<?php
// api/sendotp.php
require_once __DIR__ . '/bootstrap.php';

// Authenticate the request and get the user
$user = api_authenticate($conn);

// API uses POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error("Invalid request method. Please use POST.", 405, '405');
}

// Fetch settings for pricing check
$settings = get_settings();
$price_otp = (float)($settings['price_otp'] ?? 5.0);

// Pre-flight balance check
if ($user['balance'] < $price_otp) {
    api_error("Insufficient balance. Required: " . get_currency_symbol() . number_format($price_otp, 2), 402, '107');
}

// Validate basic parameters
$sender_id = $_POST['senderID'] ?? '';
$recipients = $_POST['recipients'] ?? '';
$template_code = $_POST['templatecode'] ?? '';

if (empty($sender_id) || empty($recipients) || empty($template_code)) {
    api_error("Missing required parameters: senderID, recipients, and templatecode are required.", 400, '400');
}

$otp_type = $_POST['otp_type'] ?? 'NUMERIC';
$otp_length = $_POST['otp_length'] ?? '6';
$otp_duration = $_POST['otp_duration'] ?? '5';
$otp_attempts = $_POST['otp_attempts'] ?? '1';
$channel = $_POST['channel'] ?? 'sms';

// Call the Termii OTP helper function
$result = send_termii_generated_otp($user, $sender_id, $recipients, $template_code, $otp_type, $otp_length, $otp_duration, $otp_attempts, $channel, $conn);

if ($result['success']) {
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'error_code' => '000',
        'verification_id' => $result['verification_id'],
        'pinId' => $result['verification_id'], // Map both Termii's pinId and verification_id
        'cost' => $result['cost'],
        'message' => 'OTP sent successfully.'
    ]);
} else {
    api_error($result['message'], 400);
}
?>
