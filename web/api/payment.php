<?php
// web/api/payment.php
require_once __DIR__ . '/bootstrap.php';

$user = mobile_authenticate($conn);
$action = $_GET['action'] ?? 'settings';

if ($action === 'settings') {
    $settings = get_settings();
    mobile_api_success([
        'manual_payment' => [
            'enabled' => !empty($settings['manual_bank_name']),
            'bank_name' => $settings['manual_bank_name'] ?? '',
            'account_name' => $settings['manual_account_name'] ?? '',
            'account_number' => $settings['manual_account_number'] ?? '',
            'instructions' => $settings['manual_payment_instructions'] ?? ''
        ],
        'vat_percentage' => (float)($settings['vat_percentage'] ?? 0),
        'currency' => get_currency_code(),
        'currency_symbol' => get_currency_symbol()
    ]);
} elseif ($action === 'submit_manual') {
    $amount = (float)$_POST['amount'];
    $reference = $_POST['reference'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');

    if ($amount <= 0 || empty($reference)) mobile_api_error('Valid amount and reference required');

    $settings = get_settings();
    $vat_percentage = (float)($settings['vat_percentage'] ?? 0);
    $vat_amount = $amount * ($vat_percentage / 100);
    $credit_amount = $amount - $vat_amount;

    $conn->begin_transaction();
    try {
        $stmt_inv = $conn->prepare("INSERT INTO invoices (user_id, status, subtotal, vat_percentage, vat_amount, total_amount) VALUES (?, 'unpaid', ?, ?, ?, ?)");
        $stmt_inv->bind_param("idddd", $user['id'], $credit_amount, $vat_percentage, $vat_amount, $amount);
        $stmt_inv->execute();
        $invoice_id = $conn->insert_id;

        $desc = "Manual Deposit Submission. Ref: " . $reference;
        $stmt_trans = $conn->prepare("INSERT INTO transactions (user_id, invoice_id, reference, type, amount, total_amount, status, gateway, description) VALUES (?, ?, ?, 'deposit', ?, ?, 'pending', 'manual', ?)");
        $stmt_trans->bind_param("iisdds", $user['id'], $invoice_id, $reference, $credit_amount, $amount, $desc);
        $stmt_trans->execute();
        $transaction_id = $conn->insert_id;

        $stmt_update = $conn->prepare("UPDATE invoices SET transaction_id = ? WHERE id = ?");
        $stmt_update->bind_param("ii", $transaction_id, $invoice_id);
        $stmt_update->execute();

        $stmt_dep = $conn->prepare("INSERT INTO manual_deposits (user_id, transaction_id, invoice_id, amount, reference_id, payment_date, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt_dep->bind_param("iiidss", $user['id'], $transaction_id, $invoice_id, $credit_amount, $reference, $date);
        $stmt_dep->execute();

        $conn->commit();
        mobile_api_success([], 'Payment proof submitted successfully');
    } catch (Exception $e) {
        $conn->rollback();
        mobile_api_error('Failed to submit payment proof');
    }
}
?>
