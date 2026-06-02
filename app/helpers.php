<?php
// app/helpers.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/PHPMailer/Exception.php';
require_once __DIR__ . '/vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/vendor/PHPMailer/SMTP.php';

function is_admin() {
    global $current_user;
    return isset($current_user) && $current_user['is_admin'] == 1 && !isset($_SESSION['original_admin_id']);
}

function is_impersonating() {
    return isset($_SESSION['original_admin_id']);
}

function get_settings() {
    // Use the pre-fetched settings from bootstrap.php if available.
    if (isset($GLOBALS['app_settings'])) {
        return $GLOBALS['app_settings'];
    }

    // Fallback to query if bootstrap hasn't run or settings aren't loaded.
    // This maintains compatibility for any scripts that might include helpers.php directly.
    global $conn;
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        $stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            $stmt->close();
        }
        // Cache the result for subsequent calls within the same request.
        $GLOBALS['app_settings'] = $settings;
    }
    return $settings;
}

/**
 * Returns the currency symbol for the configured site currency.
 * @return string The currency symbol.
 */
function get_currency_symbol() {
    $settings = get_settings();
    $currency = $settings['site_currency'] ?? 'USD';
    switch (strtoupper($currency)) {
        case 'NGN':
            return '₦';
        case 'USD':
            return '$';
        case 'EUR':
            return '€';
        case 'GBP':
            return '£';
        default:
            return '$';
    }
}

/**
 * Returns the admin email address from settings.
 * @return string The admin email address.
 */
function get_admin_email() {
    $settings = get_settings();
    return $settings['admin_email'] ?? 'admin@example.com';
}

/**
 * Returns the currency code for the configured site currency.
 * @return string The currency code (e.g., 'USD').
 */
function get_currency_code() {
    $settings = get_settings();
    return $settings['site_currency'] ?? 'USD';
}

function render_email_template($subject, $body_html, $settings) {
    $site_name = $settings['site_name'] ?? 'BulkSMS';
    $site_url = SITE_URL;
    $logo_url = !empty($settings['site_logo']) ? SITE_URL . '/' . $settings['site_logo'] : '';
    $template = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>' . htmlspecialchars($subject) . '</title><style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;margin:0;padding:0;background-color:#f4f4f4;}.wrapper{width:100%;table-layout:fixed;background-color:#f4f4f4;padding:40px 0;}.main{background-color:#ffffff;margin:0 auto;width:100%;max-width:600px;border-spacing:0;border-radius:8px;}.header{background-color:#0d6efd;color:white;padding:20px;text-align:center;border-top-left-radius:8px;border-top-right-radius:8px;}.header h1{margin:0;font-size:24px;}.header img{max-height:50px;width:auto;}.content{padding:30px;}.content h2{font-size:20px;margin-top:0;}.content p{margin-bottom:1em;line-height:1.5;}.footer{background-color:#e9ecef;color:#6c757d;padding:20px;text-align:center;font-size:12px;border-bottom-left-radius:8px;border-bottom-right-radius:8px;}.footer a{color:#0d6efd;text-decoration:none;}</style></head><body><center class="wrapper"><table class="main" role="presentation"><tr><td class="header">' . ($logo_url ? '<img src="' . $logo_url . '" alt="' . $site_name . ' Logo">' : '<h1>' . $site_name . '</h1>') . '</td></tr><tr><td class="content"><h2>' . htmlspecialchars($subject) . '</h2>' . $body_html . '</td></tr><tr><td class="footer"><p>&copy; ' . date('Y') . ' ' . $site_name . '. All rights reserved.</p><p><a href="' . $site_url . '">Visit our website</a></p></td></tr></table></center></body></html>';
    return $template;
}

function send_email($to, $subject, $message, $attachment_content = null, $attachment_filename = null) {
    $log_file = '/tmp/email.log';
    $timestamp = date('Y-m-d H:i:s');

    // Log the attempt
    $log_entry = "[{$timestamp}] --- Attempting to send email ---\n";
    $log_entry .= "To: {$to}\n";
    $log_entry .= "Subject: {$subject}\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);

    $settings = get_settings();
    $full_html_message = render_email_template($subject, $message, $settings);
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $settings['smtp_host'] ?? 'localhost';
        $mail->SMTPAuth = true;
        $mail->Username = $settings['smtp_user'] ?? '';
        $mail->Password = $settings['smtp_pass'] ?? '';
        if (($settings['smtp_encryption'] ?? 'none') !== 'none') $mail->SMTPSecure = $settings['smtp_encryption'];
        $mail->Port = (int)($settings['smtp_port'] ?? 587);

        $from_email = $settings['smtp_from_email'] ?? 'noreply@example.com';
        $from_name = $settings['smtp_from_name'] ?? SITE_NAME;
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to);

        if ($attachment_content !== null && $attachment_filename !== null) {
            $mail->addStringAttachment($attachment_content, $attachment_filename);
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $full_html_message;
        $mail->AltBody = strip_tags($message);

        // For debugging SMTP issues
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = function($str, $level) use ($log_file) {
        //     file_put_contents($log_file, "SMTP Debug: $str\n", FILE_APPEND);
        // };

        $mail->send();

        // Log success
        $success_log = "[{$timestamp}] SUCCESS: Email sent to {$to}\n\n";
        file_put_contents($log_file, $success_log, FILE_APPEND);

        return ['success' => true, 'message' => 'Email sent successfully.'];
    } catch (Exception $e) {
        // Log failure
        $error_log = "[{$timestamp}] FAILURE: Could not send email to {$to}.\n";
        $error_log .= "Mailer Error: {$mail->ErrorInfo}\n\n";
        file_put_contents($log_file, $error_log, FILE_APPEND);

        return ['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"];
    }
}

function debit_and_schedule_sms($user, $sender_id, $recipients, $message, $route, $scheduled_for_utc, $conn) {
    $errors = [];
    if (empty($sender_id)) $errors[] = "Sender ID is required.";
    if (empty($recipients)) $errors[] = "Recipients are required.";
    if (empty($message)) $errors[] = "Message is required.";
    if (empty($route)) $errors[] = "A message route must be selected.";
    if (contains_banned_word($message)) $errors[] = "Your message contains a banned word and cannot be sent.";
    if (!empty($errors)) return ['success' => false, 'message' => implode(' ', $errors)];

    $settings = get_settings();
    $price_per_sms = ($route === 'corporate') ? (float)($settings['price_sms_corp'] ?? 20.0) : (float)($settings['price_sms_promo'] ?? 10.0);

    $recipient_numbers = filter_phone_numbers($recipients);
    if (empty($recipient_numbers)) return ['success' => false, 'message' => "No valid recipient phone numbers found."];

    $units = calculate_sms_units($message);
    $max_units = (int)($settings['sms_max_units'] ?? 0);
    if ($max_units > 0 && $units > $max_units) {
        return ['success' => false, 'message' => "Your message exceeds the maximum allowed SMS pages ({$max_units})."];
    }

    $total_cost = count($recipient_numbers) * $units * $price_per_sms;

    if ($user['balance'] < $total_cost) {
        return ['success' => false, 'message' => "Insufficient balance. Required: " . get_currency_symbol() . number_format($total_cost, 2) . ", Available: " . get_currency_symbol() . number_format($user['balance'], 2)];
    }

    $conn->begin_transaction();
    try {
        // 1. Debit the user's balance
        $stmt_balance = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt_balance->bind_param("di", $total_cost, $user['id']);
        $stmt_balance->execute();
        if ($stmt_balance->affected_rows === 0) {
            throw new Exception("Failed to update user balance. User may not exist or balance update failed.");
        }
        $stmt_balance->close();

        // 2. Log the message with a 'scheduled' status
        $status = 'scheduled';
        $stmt_log = $conn->prepare("INSERT INTO messages (user_id, sender_id, recipients, message, cost, status, type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_log->bind_param("isssdss", $user['id'], $sender_id, $recipients, $message, $total_cost, $status, $route);
        if (!$stmt_log->execute()) {
            throw new mysqli_sql_exception("Failed to insert into messages table: " . $stmt_log->error);
        }
        $message_id = $conn->insert_id;
        $stmt_log->close();

        // 3. Create the scheduled task
        $payload = json_encode([
            'sender_id' => $sender_id,
            'recipients' => $recipients,
            'message' => $message,
            'route' => $route,
            'message_id' => $message_id // Link to the messages table
        ]);
        $created_at_utc = gmdate('Y-m-d H:i:s');
        $stmt_schedule = $conn->prepare("INSERT INTO scheduled_tasks (user_id, task_type, payload, scheduled_for, status, created_at) VALUES (?, 'sms', ?, ?, 'pending', ?)");
        $stmt_schedule->bind_param("isss", $user['id'], $payload, $scheduled_for_utc, $created_at_utc);
        if (!$stmt_schedule->execute()) {
            throw new mysqli_sql_exception("Failed to insert into scheduled_tasks table: " . $stmt_schedule->error);
        }
        $stmt_schedule->close();

        // 4. Log recipients
        if ($message_id > 0 && !empty($recipient_numbers)) {
            $stmt_recipient = $conn->prepare("INSERT INTO message_recipients (message_id, recipient_number, status) VALUES (?, ?, 'Scheduled')");
            foreach ($recipient_numbers as $number) {
                $clean_number = trim($number);
                if (!empty($clean_number)) {
                    $stmt_recipient->bind_param("is", $message_id, $clean_number);
                    $stmt_recipient->execute();
                }
            }
            $stmt_recipient->close();
        }

        $conn->commit();
        return ['success' => true, 'message' => "Message scheduled successfully! Cost: " . get_currency_symbol() . number_format($total_cost, 2)];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("SMS scheduling transaction failed for user {$user['id']}: " . $e->getMessage());
        return ['success' => false, 'message' => "A server error occurred while scheduling your message. The transaction has been rolled back."];
    }
}




function send_bulk_sms($user, $sender_id, $recipients, $message, $route, $conn) {
    $errors = [];
    if (empty($sender_id)) $errors[] = "Sender ID is required.";
    if (empty($recipients)) $errors[] = "Recipients are required.";
    if (empty($message)) $errors[] = "Message is required.";
    if (empty($route)) $errors[] = "A message route must be selected.";
    if (contains_banned_word($message)) $errors[] = "Your message contains a banned word and cannot be sent.";
    if (!empty($errors)) return ['success' => false, 'message' => implode(' ', $errors)];

    $settings = get_settings();
    $recipient_numbers = filter_phone_numbers($recipients);
    if (empty($recipient_numbers)) return ['success' => false, 'message' => "No valid recipient phone numbers found."];

    $units = calculate_sms_units($message);
    $max_units = (int)($settings['sms_max_units'] ?? 0);
    if ($max_units > 0 && $units > $max_units) {
        return ['success' => false, 'message' => "Your message exceeds the maximum allowed SMS pages ({$max_units})."];
    }

    // --- Existing Promotional/Corporate Route Logic ---
    $price_per_sms = ($route === 'corporate') ? (float)($settings['price_sms_corp'] ?? 20.0) : (float)($settings['price_sms_promo'] ?? 10.0);
    $total_cost = count($recipient_numbers) * $units * $price_per_sms;

    if ($user['balance'] < $total_cost) {
        return ['success' => false, 'message' => "Insufficient balance. Required: " . get_currency_symbol() . number_format($total_cost, 2) . ", Available: " . get_currency_symbol() . number_format($user['balance'], 2)];
    }

    $sms_api_key = $settings['kudisms_api_key_sms'] ?? '';
    if (empty($sms_api_key)) return ['success' => false, 'message' => 'SMS API is not configured by the administrator.'];

    $ch = curl_init();
    if ($route === 'corporate') {
        $post_data = ['token' => $sms_api_key, 'senderID' => $sender_id, 'recipients' => $recipients, 'message' => $message];
        curl_setopt($ch, CURLOPT_URL, "https://my.kudisms.net/api/corporate/sms");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    } else {
        $exploded_key_parts = array_filter(explode(":", trim($sms_api_key)));
        $api_token = $exploded_key_parts[0];
        $query_params = http_build_query(['token' => $api_token, 'senderID' => $sender_id, 'recipients' => $recipients, 'message' => $message, 'gateway' => '2']);
        curl_setopt($ch, CURLOPT_URL, "https://my.kudisms.net/api/sms?" . $query_params);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) return ['success' => false, 'message' => "cURL Error: " . $curl_error];

    $api_result = json_decode($response, true);
    if (!is_array($api_result)) $api_result = [];
    $is_successful = ($http_code == 200 && (($route === 'promotional' && isset($api_result['error_code']) && $api_result['error_code'] == '000') || ($route === 'corporate' && isset($api_result['status']) && $api_result['status'] == 'success')));

    if ($is_successful) {
        $conn->begin_transaction();
        try {
            $stmt_balance = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt_balance->bind_param("di", $total_cost, $user['id']);
            $stmt_balance->execute();

            $log_api_response = is_string($response) ? $response : json_encode($response);
            $status = 'success';
            $stmt_log = $conn->prepare("INSERT INTO messages (user_id, sender_id, recipients, message, cost, status, api_response, type, wallet_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'general')");
            $stmt_log->bind_param("isssdsss", $user['id'], $sender_id, $recipients, $message, $total_cost, $status, $log_api_response, $route);
            if (!$stmt_log->execute()) {
                throw new mysqli_sql_exception("Failed to insert into messages table: " . $stmt_log->error);
            }
            $message_id = $conn->insert_id;
            $stmt_log->close();

            if ($message_id > 0 && !empty($recipient_numbers)) {
                $stmt_recipient = $conn->prepare("INSERT INTO message_recipients (message_id, recipient_number, status) VALUES (?, ?, 'Sent')");
                foreach ($recipient_numbers as $number) {
                    $clean_number = trim($number);
                    if (!empty($clean_number)) {
                        $stmt_recipient->bind_param("is", $message_id, $clean_number);
                        $stmt_recipient->execute();
                    }
                }
                $stmt_recipient->close();
            }
            $conn->commit();
            return ['success' => true, 'message' => "Message sent successfully! Cost: " . get_currency_symbol() . number_format($total_cost, 2), 'data' => $api_result];
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            return ['success' => false, 'message' => "A server error occurred while logging the transaction."];
        }
    } else {
        $error_msg = $api_result['msg'] ?? ($api_result['error_code'] ? "API Error Code: " . $api_result['error_code'] : 'An unknown error occurred.');
        return ['success' => false, 'message' => "API Error: " . $error_msg, 'data' => $api_result];
    }
}

function debit_and_schedule_voice_tts($user, $caller_id, $recipients, $message, $scheduled_for_utc, $conn) {
    $errors = [];
    if (empty($caller_id)) $errors[] = "Caller ID is required.";
    if (empty($recipients)) $errors[] = "Recipients are required.";
    if (empty($message)) $errors[] = "Message is required.";
    if (contains_banned_word($message)) $errors[] = "Your message contains a banned word and cannot be sent.";
    if (!empty($errors)) return ['success' => false, 'message' => implode(' ', $errors)];

    $settings = get_settings();
    $price_per_call = (float)($settings['price_voice_tts'] ?? 30.0);
    $recipient_numbers = preg_split('/[\s,;\n]+/', $recipients, -1, PREG_SPLIT_NO_EMPTY);
    $total_cost = count($recipient_numbers) * $price_per_call;

    if ($user['balance'] < $total_cost) {
        return ['success' => false, 'message' => "Insufficient balance. Required: " . get_currency_symbol() . number_format($total_cost, 2) . ", Available: " . get_currency_symbol() . number_format($user['balance'], 2)];
    }

    $conn->begin_transaction();
    try {
        // 1. Debit the user's balance
        $stmt_balance = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt_balance->bind_param("di", $total_cost, $user['id']);
        $stmt_balance->execute();
        $stmt_balance->close();

        // 2. Log the message with a 'scheduled' status
        $status = 'scheduled';
        $stmt_log = $conn->prepare("INSERT INTO messages (user_id, sender_id, recipients, message, cost, status, type) VALUES (?, ?, ?, ?, ?, ?, 'voice_tts')");
        $stmt_log->bind_param("isssds", $user['id'], $caller_id, $recipients, $message, $total_cost, $status);
        $stmt_log->execute();
        $message_id = $conn->insert_id;
        $stmt_log->close();

        // 3. Create the scheduled task
        $payload = json_encode(['caller_id' => $caller_id, 'recipients' => $recipients, 'message' => $message, 'message_id' => $message_id]);
        $created_at_utc = gmdate('Y-m-d H:i:s');
        $stmt_schedule = $conn->prepare("INSERT INTO scheduled_tasks (user_id, task_type, payload, scheduled_for, status, created_at) VALUES (?, 'voice_tts', ?, ?, 'pending', ?)");
        $stmt_schedule->bind_param("isss", $user['id'], $payload, $scheduled_for_utc, $created_at_utc);
        $stmt_schedule->execute();
        $stmt_schedule->close();

        // 4. Log recipients
        if ($message_id > 0 && !empty($recipient_numbers)) {
            $stmt_recipient = $conn->prepare("INSERT INTO message_recipients (message_id, recipient_number, status) VALUES (?, ?, 'Scheduled')");
            foreach ($recipient_numbers as $number) {
                $clean_number = trim($number);
                if (!empty($clean_number)) {
                    $stmt_recipient->bind_param("is", $message_id, $clean_number);
                    $stmt_recipient->execute();
                }
            }
            $stmt_recipient->close();
        }

        $conn->commit();
        return ['success' => true, 'message' => "Voice message scheduled successfully! Cost: " . get_currency_symbol() . number_format($total_cost, 2)];

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Voice/TTS scheduling transaction failed for user {$user['id']}: " . $e->getMessage());
        return ['success' => false, 'message' => "A server error occurred while scheduling your message."];
    }
}


function send_voice_tts($user, $caller_id, $recipients, $message, $conn) {
    $errors = [];
    if (empty($caller_id)) $errors[] = "Caller ID is required.";
    if (empty($recipients)) $errors[] = "Recipients are required.";
    if (empty($message)) $errors[] = "Message is required.";
    if (contains_banned_word($message)) $errors[] = "Your message contains a banned word and cannot be sent.";
    if (!empty($errors)) return ['success' => false, 'message' => implode(' ', $errors)];

    $settings = get_settings();
    $price_per_call = (float)($settings['price_voice_tts'] ?? 30.0);
    $recipient_numbers = preg_split('/[\s,;\n]+/', $recipients, -1, PREG_SPLIT_NO_EMPTY);
    $total_cost = count($recipient_numbers) * $price_per_call;

    if ($user['balance'] < $total_cost) return ['success' => false, 'message' => "Insufficient balance."];

    $api_key = $settings['kudisms_api_key_tts'] ?? '';
    if (empty($api_key)) return ['success' => false, 'message' => 'Voice/TTS API is not configured by the administrator.'];

    $exploded_key_parts = array_filter(explode(":", trim($api_key)));
    $api_token = $exploded_key_parts[0];
    $api_url = "https://my.kudisms.net/api/texttospeech";
    $query_params = http_build_query(['token' => $api_token, 'callerID' => $caller_id, 'recipients' => $recipients, 'message' => $message]);
    $full_url = $api_url . "?" . $query_params;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $full_url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) return ['success' => false, 'message' => "cURL Error: " . $curl_error];

    $api_result = json_decode($response, true);
    if (!is_array($api_result)) $api_result = [];
    $is_successful = ($http_code == 200 && isset($api_result['status']) && $api_result['status'] == 'success');

    if ($is_successful) {
        $conn->begin_transaction();
        try {
            $stmt_balance = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt_balance->bind_param("di", $total_cost, $user['id']);
            $stmt_balance->execute();

            $log_api_response = is_string($response) ? $response : json_encode($response);
            $stmt_log = $conn->prepare("INSERT INTO messages (user_id, sender_id, recipients, message, cost, status, type, api_response) VALUES (?, ?, ?, ?, ?, 'success', 'voice_tts', ?)");
            $stmt_log->bind_param("isssds", $user['id'], $caller_id, $recipients, $message, $total_cost, $log_api_response);
            if (!$stmt_log->execute()) {
                throw new mysqli_sql_exception("Failed to insert into messages table for TTS: " . $stmt_log->error);
            }
            $message_id = $stmt_log->insert_id;
            $stmt_log->close();

            if ($message_id > 0 && !empty($recipient_numbers)) {
                $stmt_recipient = $conn->prepare("INSERT INTO message_recipients (message_id, recipient_number, status) VALUES (?, ?, 'Sent')");
                foreach ($recipient_numbers as $number) {
                    $stmt_recipient->bind_param("is", $message_id, $number);
                    $stmt_recipient->execute();
                }
                $stmt_recipient->close();
            }

            $conn->commit();
            return ['success' => true, 'message' => "Voice message sent successfully!"];
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            error_log("Voice/TTS DB transaction failed: " . $exception->getMessage());
            return ['success' => false, 'message' => "Database transaction failed."];
        }
    } else {
        $error_msg = $api_result['msg'] ?? 'An unknown error occurred with the Voice/TTS gateway.';
        return ['success' => false, 'message' => "API Error: " . $error_msg, 'data' => $api_result];
    }
}

function send_voice_audio_api($user, $caller_id, $recipients, $audio_url, $conn) {
    $errors = [];
    if (empty($caller_id)) $errors[] = "Caller ID is required.";
    if (empty($recipients)) $errors[] = "Recipients are required.";
    if (empty($audio_url)) $errors[] = "Audio URL is required.";
    if (!empty($errors)) return ['success' => false, 'message' => implode(' ', $errors)];

    $settings = get_settings();
    $price_per_call = (float)($settings['price_voice_audio'] ?? 35.0);
    $recipient_numbers = preg_split('/[\s,;\n]+/', $recipients, -1, PREG_SPLIT_NO_EMPTY);
    $total_cost = count($recipient_numbers) * $price_per_call;

    if ($user['balance'] < $total_cost) {
        return ['success' => false, 'message' => "Insufficient balance."];
    }

    $api_key = $settings['kudisms_api_key_tts'] ?? '';
    if (empty($api_key)) {
        return ['success' => false, 'message' => 'Voice API is not configured by the administrator.'];
    }

    $api_url = "https://my.kudisms.net/api/voice";
    $post_data = ['token' => $api_key, 'callerID' => $caller_id, 'recipients' => $recipients, 'audio' => $audio_url];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) return ['success' => false, 'message' => "cURL Error: " . $curl_error];

    $api_result = json_decode($response, true);
    if (!is_array($api_result)) $api_result = [];
    $is_successful = ($http_code == 200 && isset($api_result['status']) && $api_result['status'] == 'success');

    if ($is_successful) {
        $conn->begin_transaction();
        try {
            $final_cost = $api_result['cost'] ?? $total_cost;
            $stmt_balance = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt_balance->bind_param("di", $final_cost, $user['id']);
            $stmt_balance->execute();

            $log_message = "Voice Audio message sent from URL: " . $audio_url;
            $log_type = 'voice_audio';
            $stmt_log = $conn->prepare("INSERT INTO messages (user_id, sender_id, recipients, message, cost, status, type, api_response) VALUES (?, ?, ?, ?, ?, 'success', ?, ?)");
            $stmt_log->bind_param("isssdss", $user['id'], $caller_id, $recipients, $log_message, $final_cost, $log_type, $response);
            if (!$stmt_log->execute()) {
                throw new mysqli_sql_exception("Failed to insert into messages table for Voice Audio: " . $stmt_log->error);
            }
            $message_id = $stmt_log->insert_id;
            $stmt_log->close();

            if ($message_id > 0 && !empty($recipient_numbers)) {
                $stmt_recipient = $conn->prepare("INSERT INTO message_recipients (message_id, recipient_number, status, api_message_id) VALUES (?, ?, 'Sent', ?)");
                if(isset($api_result['data']) && is_array($api_result['data'])){
                    foreach ($api_result['data'] as $recipient_data) {
                        $parts = explode('|', $recipient_data);
                        if (count($parts) == 2) {
                            $stmt_recipient->bind_param("iss", $message_id, $parts[0], $parts[1]);
                            $stmt_recipient->execute();
                        }
                    }
                }
                $stmt_recipient->close();
            }
            $conn->commit();
            return ['success' => true, 'message' => $api_result['msg'] ?? "Voice message sent successfully!"];
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            error_log("Voice Audio DB transaction failed: " . $exception->getMessage());
            return ['success' => false, 'message' => "Database transaction failed."];
        }
    } else {
        $error_msg = $api_result['msg'] ?? 'An unknown error occurred with the Voice API.';
        return ['success' => false, 'message' => "API Error: " . $error_msg, 'data' => $api_result];
    }
}


function is_banned($value, $type) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM banned WHERE value = ? AND type = ?");
    $stmt->bind_param("ss", $value, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result->num_rows > 0;
}

function contains_banned_word($string) {
    global $conn;
    static $banned_words = null;
    if ($banned_words === null) {
        $banned_words = [];
        $stmt = $conn->prepare("SELECT `value` FROM `banned` WHERE `type` = 'word'");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $banned_words[] = preg_quote($row['value'], '/');
            }
            $stmt->close();
        }
    }
    if (empty($banned_words)) return false;
    $pattern = '/\b(' . implode('|', $banned_words) . ')\b/i';
    return preg_match($pattern, $string) > 0;
}

function get_active_notifications() {
    global $conn;
    $current_page = basename($_SERVER['PHP_SELF']);
    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("SELECT message, type, placement FROM notifications WHERE is_active = 1 AND (start_time IS NULL OR start_time <= ?) AND (end_time IS NULL OR end_time >= ?)");
    $stmt->bind_param("ss", $now, $now);
    $stmt->execute();
    $result = $stmt->get_result();
    $all_notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $display_notifications = [];
    foreach ($all_notifications as $notif) {
        $placements = explode(',', $notif['placement']);
        $placements = array_map('trim', $placements);
        if (in_array('all', $placements) || in_array($current_page, $placements)) {
            $display_notifications[] = $notif;
        }
    }
    return $display_notifications;
}

function set_callback_url_api($callback_url) {
    $settings = get_settings();
    $api_key = $settings['kudisms_api_key_sms'] ?? '';
    if (empty($api_key)) return ['success' => false, 'message' => 'SMS API Key is not configured.'];
    $api_url = "https://api.my.kudisms.net/callback";
    $post_data = ['token' => $api_key, 'url'   => $callback_url];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    if ($response === false) return ['success' => false, 'message' => "cURL Error: " . $curl_error];
    $api_result = json_decode($response, true);
    if (!is_array($api_result)) $api_result = [];
    if ($http_code == 200 && isset($api_result['status']) && $api_result['status'] == 'success') {
        return ['success' => true, 'message' => $api_result['msg'] ?? 'Callback URL updated successfully.'];
    } else {
        return ['success' => false, 'message' => $api_result['msg'] ?? 'Failed to update callback URL.'];
    }
}

function submit_sender_id_api($sender_id, $sample_message) {
    $settings = get_settings();
    $api_key = $settings['kudisms_api_key_senderid'] ?? '';
    if (empty($api_key)) return ['success' => false, 'message' => 'Sender ID API key is not configured by the administrator.'];
    $api_url = "https://my.kudisms.net/api/senderID";
    $post_data = ['token' => $api_key, 'senderID' => $sender_id, 'message' => $sample_message];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    if ($response === false) return ['success' => false, 'message' => "cURL Error: " . $curl_error];
    $api_result = json_decode($response, true);
    if (!is_array($api_result)) $api_result = [];
    if ($http_code == 200 && isset($api_result['status']) && $api_result['status'] == 'success') {
        return ['success' => true, 'message' => $api_result['msg'] ?? 'Sender ID submitted successfully.', 'data' => $api_result];
    } else {
        $error_msg = $api_result['msg'] ?? 'An unknown error occurred with the Sender ID API.';
        return ['success' => false, 'message' => "API Error: " . $error_msg, 'data' => $api_result];
    }
}

function check_sender_id_api($sender_id) {
    $settings = get_settings();
    $api_key = $settings['kudisms_api_key_senderid'] ?? '';
    if (empty($api_key)) return ['success' => false, 'message' => 'Sender ID API key is not configured by the administrator.'];
    $api_url = "https://my.kudisms.net/api/check_senderID";
    $query_params = http_build_query(['token' => $api_key, 'senderID' => $sender_id]);
    $full_url = $api_url . "?" . $query_params;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $full_url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    if ($response === false) return ['success' => false, 'message' => "cURL Error: " . $curl_error];
    $api_result = json_decode($response, true);
    if (!is_array($api_result)) $api_result = [];
    if ($http_code == 200 && isset($api_result['status']) && $api_result['status'] == 'success') {
        return ['success' => true, 'message' => $api_result['msg'] ?? 'Status checked successfully.', 'data' => $api_result];
    } else {
        $error_msg = $api_result['msg'] ?? 'Could not check Sender ID status.';
        return ['success' => false, 'message' => "API Error: " . $error_msg, 'data' => $api_result];
    }
}

/**
 * Converts a UTC datetime string to the user's configured timezone for display.
 *
 * @param string $utc_datetime_string The datetime string in UTC.
 * @return string The formatted datetime string in the user's timezone.
 */
function format_date_for_display($utc_datetime_string) {
    if (empty($utc_datetime_string)) {
        return 'N/A';
    }
    try {
        $settings = get_settings();
        $user_timezone_str = $settings['site_timezone'] ?? 'UTC';
        $user_timezone = new DateTimeZone($user_timezone_str);

        $utc_time = new DateTime($utc_datetime_string, new DateTimeZone('UTC'));
        $utc_time->setTimezone($user_timezone);

        return $utc_time->format('M j, Y g:i A');
    } catch (Exception $e) {
        // Log error or handle it gracefully
        error_log('Error formatting date: ' . $e->getMessage());
        // Fallback to displaying the original string or a generic error
        return $utc_datetime_string;
    }
}

function get_menu_items($location) {
    global $conn;
    $menu_items = [];
    $sql = "SELECT label, link FROM menus WHERE location = ? ORDER BY sort_order ASC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $location);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $menu_items[] = $row;
        }
        $stmt->close();
    }
    return $menu_items;
}

function get_visible_pages() {
    global $conn;
    $pages = [];
    // Only fetch pages that should be visible in a menu.
    // 'private' is for logged-in users, 'public' is for everyone.
    // 'hidden' pages are accessed via direct link only.
    $sql = "SELECT title, slug FROM pages WHERE visibility IN ('public', 'private') ORDER BY title ASC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $pages[] = $row;
        }
        $stmt->close();
    }
    return $pages;
}

function submit_corporate_sender_id_api($sender_id, $document_paths) {
    $settings = get_settings();
    $api_key = $settings['kudisms_api_key_senderid'] ?? '';
    if (empty($api_key)) {
        return ['success' => false, 'message' => 'Sender ID API key is not configured by the administrator.'];
    }

    // Assuming the corporate API endpoint is here. This might need to be adjusted.
    $api_url = "https://my.kudisms.net/api/corporate/senderID";

    $post_data = [
        'token' => $api_key,
        'senderID' => $sender_id,
    ];

    // Add files to the post data
    foreach ($document_paths as $key => $path) {
        // Use the absolute path on the server for CURLFile
        $absolute_path = __DIR__ . '/../' . $path;
        if (file_exists($absolute_path)) {
            $post_data[$key] = new CURLFile(realpath($absolute_path));
        }
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'message' => "cURL Error: " . $curl_error];
    }

    $api_result = json_decode($response, true);
    if (!is_array($api_result)) $api_result = [];
    if ($http_code == 200 && isset($api_result['status']) && $api_result['status'] == 'success') {
        return ['success' => true, 'message' => $api_result['msg'] ?? 'Corporate Sender ID submitted successfully.', 'data' => $api_result];
    } else {
        $error_msg = $api_result['msg'] ?? 'An unknown error occurred with the Corporate Sender ID API.';
        return ['success' => false, 'message' => "API Error: " . $error_msg, 'data' => $api_result];
    }
}

function submit_airtel_sender_id_api($data) {
    $settings = get_settings();
    $api_key = $settings['kudisms_api_key_senderid'] ?? '';
    if (empty($api_key)) {
        return ['success' => false, 'message' => 'Sender ID API key is not configured by the administrator.'];
    }

    // Assuming the Airtel specific API endpoint is here. This might need to be adjusted.
    $api_url = "https://my.kudisms.net/api/senderID/airtel";

    $post_data = [
        'token' => $api_key,
        'company_name' => $data['company_name'],
        'senderID' => $data['sender_id'],
        'nature_of_business' => $data['nature_of_business'],
        'service_description' => $data['service_description'],
        'message' => $data['sample_message'],
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'message' => "cURL Error: " . $curl_error];
    }

    $api_result = json_decode($response, true);
    if (!is_array($api_result)) $api_result = [];
    if ($http_code == 200 && isset($api_result['status']) && $api_result['status'] == 'success') {
        return ['success' => true, 'message' => $api_result['msg'] ?? 'Airtel Sender ID submitted successfully.', 'data' => $api_result];
    } else {
        $error_msg = $api_result['msg'] ?? 'An unknown error occurred with the Airtel Sender ID API.';
        return ['success' => false, 'message' => "API Error: " . $error_msg, 'data' => $api_result];
    }
}

/**
 * Generates a CSRF token, stores it in the session, and returns it.
 *
 * @return string The generated CSRF token.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a submitted CSRF token against the one in the session.
 *
 * @param string $token The token submitted from a form.
 * @return bool True if the token is valid, false otherwise.
 */
function validate_csrf_token($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

/**
 * Calculates the number of SMS units based on message length and settings.
 *
 * @param string $message The SMS message content.
 * @return int The number of units.
 */
function calculate_sms_units($message) {
    $settings = get_settings();
    $chars_1unit = max(1, (int)($settings['sms_chars_1unit'] ?? 160));
    $chars_multunit = max(1, (int)($settings['sms_chars_multunit'] ?? 153));

    $length = mb_strlen($message, 'UTF-8');

    if ($length <= $chars_1unit) {
        return 1;
    } else {
        return ceil($length / $chars_multunit);
    }
}

/**
 * Filters a string of recipients, removes invalid characters, and returns an array of valid numbers.
 *
 * @param string $recipients_str Comma, space, or newline separated recipient numbers.
 * @return array Array of valid phone numbers.
 */
function filter_phone_numbers($recipients_str) {
    $raw_numbers = preg_split('/[\s,;\n]+/', $recipients_str, -1, PREG_SPLIT_NO_EMPTY);
    $valid_numbers = [];

    foreach ($raw_numbers as $number) {
        // Remove all non-digit characters except maybe a leading plus
        $clean_number = preg_replace('/(?<!^)\+|[^\d+]/', '', trim($number));

        // Basic validation: length between 10 and 15 digits (including optional +)
        $digit_only = preg_replace('/\D/', '', $clean_number);
        if (strlen($digit_only) >= 10 && strlen($digit_only) <= 15) {
            $valid_numbers[] = $clean_number;
        }
    }

    return array_unique($valid_numbers);
}

function send_otp($user, $sender_id, $recipients, $otp, $template_code, $conn) {
    $settings = get_settings();
    $price_otp = (float)($settings['price_otp'] ?? 5.0);
    $recipient_numbers = filter_phone_numbers($recipients);
    if (empty($recipient_numbers)) {
        return ['success' => false, 'message' => 'No valid recipient phone numbers found.'];
    }
    $total_cost = count($recipient_numbers) * $price_otp;

    if ($user['balance'] < $total_cost) {
        return ['success' => false, 'message' => 'Insufficient balance.'];
    }

    $api_token = $settings['otp_api_key'] ?? $settings['kudisms_api_key_sms'] ?? '';
    if (empty($api_token)) {
        return ['success' => false, 'message' => 'OTP API is not configured by the administrator.'];
    }
    
    $exploded_key_parts = array_filter(explode(":", trim($api_token)));
    $api_token = $exploded_key_parts[0];

    $params = [
        'token' => $api_token,
        'senderID' => $sender_id,
        'recipients' => $recipients,
        'otp' => $otp,
        'appnamecode' => $settings['site_name'] ?? 'PhilmoreSMS',
        'templatecode' => $template_code
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://my.kudisms.net/api/otp');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'message' => "cURL Error: " . $curl_error];
    }

    $api_result = json_decode($response, true);
    if (!is_array($api_result)) $api_result = [];

    if ($http_code == 200 && isset($api_result['status']) && $api_result['status'] == 'success') {
        $conn->begin_transaction();
        try {
            $stmt_balance = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt_balance->bind_param("di", $total_cost, $user['id']);
            $stmt_balance->execute();
            $stmt_balance->close();

            $message_summary = "Pre-generated OTP sent via API. Verification ID: " . ($api_result['verification_id'] ?? 'N/A');
            $stmt_log = $conn->prepare("INSERT INTO messages (user_id, sender_id, recipients, message, cost, status, type, api_response) VALUES (?, ?, ?, ?, ?, 'success', 'sms_debit', ?)");
            $stmt_log->bind_param("isssds", $user['id'], $sender_id, $recipients, $message_summary, $total_cost, $response);
            $stmt_log->execute();
            $stmt_log->close();

            $conn->commit();
            return ['success' => true, 'message' => 'OTP sent successfully.', 'data' => $api_result];
        } catch (Exception $e) {
            $conn->rollback();
            return ['success' => false, 'message' => 'Database error during transaction: ' . $e->getMessage()];
        }
    } else {
        $error_msg = $api_result['msg'] ?? 'An unknown error occurred with the OTP gateway.';
        return ['success' => false, 'message' => "API Error: " . $error_msg, 'data' => $api_result];
    }
}

function verify_otp($user, $verification_id, $otp, $conn) {
    $settings = get_settings();
    $api_token = $settings['otp_api_key'] ?? $settings['kudisms_api_key_sms'] ?? '';
    if (empty($api_token)) {
        return ['success' => false, 'message' => 'OTP API is not configured by the administrator.'];
    }
    
    $exploded_key_parts = array_filter(explode(":", trim($api_token)));
    $api_token = $exploded_key_parts[0];

    $params = [
        'token' => $api_token,
        'verification_id' => $verification_id,
        'otp' => $otp
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://my.kudisms.net/api/verifyotp');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'message' => "cURL Error: " . $curl_error];
    }

    $api_result = json_decode($response, true);
    if (!is_array($api_result)) $api_result = [];

    if ($http_code == 200 && isset($api_result['status']) && $api_result['status'] == 'success') {
        return ['success' => true, 'message' => 'OTP verified successfully.', 'data' => $api_result];
    } else {
        $error_msg = $api_result['msg'] ?? 'OTP verification failed.';
        return ['success' => false, 'message' => "API Error: " . $error_msg, 'data' => $api_result];
    }
}

?>
