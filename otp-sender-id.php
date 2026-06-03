<?php
$page_title = 'OTP Sender ID Management';
include 'includes/header.php';

$errors = [];
$success = '';

// Handle new OTP Sender ID submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_sender_id'])) {
    $sender_id = trim($_POST['sender_id']);
    $sample_message = trim($_POST['sample_message']);

    // Validation
    if (empty($sender_id)) {
        $errors[] = "Sender ID cannot be empty.";
    } elseif (strlen($sender_id) > 11) {
        $errors[] = "Sender ID cannot be more than 11 characters.";
    }
    if (empty($sample_message)) {
        $errors[] = "Sample message is required.";
    }

    // Check for banned words
    if (contains_banned_word($sender_id)) {
        $errors[] = "The Sender ID contains a banned word.";
    }
    if (contains_banned_word($sample_message)) {
        $errors[] = "The sample message contains a banned word.";
    }

    if (empty($errors)) {
        // Check if the user already submitted this OTP sender ID
        $stmt_check = $conn->prepare("SELECT id FROM sender_ids WHERE user_id = ? AND sender_id = ? AND type = 'otp'");
        $stmt_check->bind_param("is", $user_id, $sender_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $errors[] = "You have already submitted this OTP Sender ID.";
        } else {
            // Call the Termii OTP Sender ID API
            $api_result = submit_otp_sender_id_api($sender_id, $sample_message);

            if ($api_result['success']) {
                // API submission was successful, now save to our database with type = 'otp'
                $stmt = $conn->prepare("INSERT INTO sender_ids (user_id, sender_id, sample_message, status, type, api_response) VALUES (?, ?, ?, 'pending', 'otp', ?)");
                $api_response_str = json_encode($api_result['data']);
                $stmt->bind_param("isss", $user_id, $sender_id, $sample_message, $api_response_str);

                if ($stmt->execute()) {
                    $success = "OTP Sender ID submitted successfully to Termii. It is now pending review.";
                } else {
                    $errors[] = "Failed to save OTP Sender ID submission locally. Please contact support.";
                }
                $stmt->close();
            } else {
                $errors[] = "Gateway Error: " . $api_result['message'];
            }
        }
        $stmt_check->close();
    }
}

// Fetch user's submitted OTP sender IDs
$user_sender_ids = [];
$stmt = $conn->prepare("SELECT sender_id, status, created_at FROM sender_ids WHERE user_id = ? AND type = 'otp' ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $user_sender_ids[] = $row;
}
$stmt->close();

?>

<div class="row">
    <!-- Submit OTP Sender ID Form -->
    <div class="col-md-5">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Register New OTP Sender ID (Termii)</h3></div>
            <form action="otp-sender-id.php" method="POST">
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?><p class="mb-0"><?php echo $error; ?></p><?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <p class="mb-0"><?php echo $success; ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="sender_id">Sender ID</label>
                        <input type="text" class="form-control" name="sender_id" placeholder="Max 11 characters" maxlength="11" required>
                        <small class="form-text text-muted">This will be used for OTP verification messages via Termii.</small>
                    </div>
                    <div class="form-group">
                        <label for="sample_message">Sample OTP Message</label>
                        <textarea class="form-control" name="sample_message" rows="3" placeholder="e.g., Your company verification code is [OTP]. Expires in 5 minutes." required></textarea>
                         <small class="form-text text-muted">Provide a sample containing the <strong>[OTP]</strong> placeholder and company name.</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" name="submit_sender_id" class="btn btn-primary">Submit for Review</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Submitted OTP Sender IDs List -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">My OTP Sender IDs</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                            <th>Sender ID</th>
                            <th>Status</th>
                            <th>Date Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($user_sender_ids)): ?>
                                <tr><td colspan="3" class="text-center">You have not submitted any OTP Sender IDs yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($user_sender_ids as $id_data): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($id_data['sender_id']); ?></td>
                                    <td>
                                        <?php
                                            $status = htmlspecialchars($id_data['status']);
                                            $badge_class = 'bg-secondary';
                                            if ($status == 'approved') {
                                                $badge_class = 'bg-success';
                                            } elseif ($status == 'rejected') {
                                                $badge_class = 'bg-danger';
                                            } elseif ($status == 'pending') {
                                                $badge_class = 'bg-warning text-dark';
                                            }
                                            echo "<span class='badge " . $badge_class . "'>" . ucfirst($status) . "</span>";
                                        ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($id_data['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
