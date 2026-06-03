<?php
// api/otp_senderID.php
require_once __DIR__ . '/bootstrap.php';

// Authenticate the request and get the user
$user = api_authenticate($conn);

// Get parameters from POST
$sender_id = $_POST['senderID'] ?? '';
$sample_message = $_POST['message'] ?? '';

// Validation
if (empty($sender_id) || empty($sample_message)) {
    api_error("Missing required parameters: senderID and message are required.", 400, '400');
}
if (strlen($sender_id) > 11) {
    api_error("Sender ID must not be more than 11 characters.", 400, '400');
}

// Check if this OTP sender ID already exists for the user
$stmt_check = $conn->prepare("SELECT id FROM sender_ids WHERE user_id = ? AND sender_id = ? AND type = 'otp'");
$stmt_check->bind_param("is", $user['id'], $sender_id);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    api_error("You have already registered this OTP Sender ID.", 400, '400');
}
$stmt_check->close();

// Call the Termii OTP Sender ID API
$api_result = submit_otp_sender_id_api($sender_id, $sample_message);

if ($api_result['success']) {
    // Insert into the database
    $stmt = $conn->prepare("INSERT INTO sender_ids (user_id, sender_id, sample_message, status, type, api_response) VALUES (?, ?, ?, 'pending', 'otp', ?)");
    $api_response_str = json_encode($api_result['data']);
    $stmt->bind_param("isss", $user['id'], $sender_id, $sample_message, $api_response_str);

    if ($stmt->execute()) {
        // Notify admin about the new OTP sender ID request
        $admin_email = get_admin_email();
        $subject = "New OTP Sender ID Submission for Review";
        $message = "<p>A new OTP Sender ID has been submitted for approval:</p><ul>" .
                   "<li>User: " . htmlspecialchars($user['username']) . "</li>" .
                   "<li>Sender ID: " . htmlspecialchars($sender_id) . "</li>" .
                   "<li>Sample Message: " . htmlspecialchars($sample_message) . "</li></ul>" .
                   "<p>You can approve or deny it in the admin panel.</p>";
        send_email($admin_email, $subject, $message);

        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "error_code" => "000",
            "msg" => "OTP sender ID submitted successfully"
        ]);
    } else {
        api_error("Failed to save OTP Sender ID submission locally. Please try again.", 500, '500');
    }
    $stmt->close();
} else {
    api_error("Gateway Error: " . $api_result['message'], 400, '400');
}
?>
