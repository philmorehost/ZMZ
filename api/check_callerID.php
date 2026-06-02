<?php
// api/check_callerID.php
require_once __DIR__ . '/bootstrap.php';

// Authenticate the request and get the user
$user = api_authenticate($conn);

// Get parameters from either POST or GET
$caller_id = $_REQUEST['callerID'] ?? $_REQUEST['caller_id'] ?? '';

if (empty($caller_id)) {
    api_error("Missing required parameter: callerID.", 400, '400');
}

// Query the database for the status
$stmt = $conn->prepare("SELECT status FROM caller_ids WHERE user_id = ? AND caller_id = ?");
$stmt->bind_param("is", $user['id'], $caller_id);
$stmt->execute();
$result = $stmt->get_result();
$caller_id_data = $result->fetch_assoc();
$stmt->close();

if ($caller_id_data) {
    $status = $caller_id_data['status'];
    $message = "The Caller ID is currently " . $status . ".";
    if ($status === 'approved') {
        $message = "The Caller ID has been approved";
    } elseif ($status === 'rejected') {
        $message = "The Caller ID has been rejected. Please contact support.";
    } elseif ($status === 'pending') {
        $message = "The Caller ID is pending review.";
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "error_code" => "000",
        "msg" => $message
    ]);
} else {
    api_error("The specified Caller ID was not found for your account.", 404, '404');
}
?>
