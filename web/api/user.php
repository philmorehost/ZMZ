<?php
// web/api/user.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);
$action = $_GET['action'] ?? 'profile';

if ($action === 'profile') {
    mobile_api_success([
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'phone' => $user['phone_number'],
            'balance' => (float)$user['balance'],
            'referral_code' => $user['referral_code'],
            'created_at' => $user['created_at']
        ]
    ]);
} elseif ($action === 'update') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($username === '' || $email === '' || $phone === '') {
        mobile_api_error('Username, email and phone are required');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        mobile_api_error('A valid email address is required');
    }

    // Prevent taking an email/username that belongs to another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
    $stmt->bind_param("ssi", $email, $username, $user['id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        mobile_api_error('Username or email is already taken');
    }
    $stmt->close();

    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone_number = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username, $email, $phone, $user['id']);
    if ($stmt->execute()) {
        mobile_api_success([
            'user' => ['username' => $username, 'email' => $email, 'phone' => $phone]
        ], 'Profile updated successfully');
    } else {
        mobile_api_error('Failed to update profile');
    }
    $stmt->close();
} elseif ($action === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if (empty($current_password) || empty($new_password)) {
        mobile_api_error('Current password and new password are required');
    }
    if (strlen($new_password) < 6) {
        mobile_api_error('New password must be at least 6 characters');
    }
    if (!password_verify($current_password, $user['password'])) {
        mobile_api_error('Current password is incorrect');
    }

    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $user['id']);
    if ($stmt->execute()) {
        mobile_api_success([], 'Password changed successfully');
    } else {
        mobile_api_error('Failed to change password');
    }
    $stmt->close();
} else {
    mobile_api_error('Invalid action');
}
?>
