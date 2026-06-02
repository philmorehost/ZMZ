<?php
// api/callerID.php
require_once __DIR__ . '/bootstrap.php';

// Authenticate the request and get the user
$user = api_authenticate($conn);

// Get parameters from POST
$caller_id = $_POST['callerID'] ?? $_POST['caller_id'] ?? '';

// Validation
if (empty($caller_id)) {
    api_error("Missing required parameter: callerID is required.", 400, '400');
}
if (!is_numeric($caller_id)) {
    api_error("Caller ID must be a numeric phone number.", 400, '400');
}

// Check if this caller ID already exists for the user
$stmt_check = $conn->prepare("SELECT id FROM caller_ids WHERE user_id = ? AND caller_id = ?");
$stmt_check->bind_param("is", $user['id'], $caller_id);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    api_error("You have already registered this Caller ID.", 400, '400');
}
$stmt_check->close();

// Insert into the database
$stmt = $conn->prepare("INSERT INTO caller_ids (user_id, caller_id, status) VALUES (?, ?, 'pending')");
$stmt->bind_param("is", $user['id'], $caller_id);

if ($stmt->execute()) {
    // Notify admin about the new caller ID request
    $admin_email = get_admin_email();
    $subject = "New Caller ID Submission for Review";
    $message = "<p>A new Caller ID has been submitted for approval:</p><ul>" .
               "<li>User: " . htmlspecialchars($user['username']) . "</li>" .
               "<li>Caller ID: " . htmlspecialchars($caller_id) . "</li></ul>" .
               "<p>You can approve or deny it in the admin panel.</p>";
    send_email($admin_email, $subject, $message);

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "error_code" => "000",
        "msg" => "Caller ID submitted successfully"
    ]);
} else {
    api_error("Failed to submit Caller ID. Please try again.", 500, '500');
}

$stmt->close();
?>
