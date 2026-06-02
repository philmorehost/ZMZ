<?php
$page_title = 'Developer API';
require_once 'app/bootstrap.php';

include 'includes/header.php';
?>
<style>
    /* Premium Styling Theme for Developer API Documentation */
    :root {
        --api-primary: #6366f1; /* Indigo */
        --api-secondary: #0ea5e9; /* Sky Blue */
        --api-success: #10b981; /* Emerald */
        --api-warning: #f59e0b; /* Amber */
        --api-danger: #ef4444; /* Rose */
        --api-dark-bg: #0f172a; /* Slate 900 */
        --api-code-bg: #1e293b; /* Slate 800 */
        --api-border: #e2e8f0; /* Slate 200 */
        --api-text-muted: #64748b; /* Slate 500 */
    }

    .api-container {
        font-family: 'Poppins', sans-serif;
    }

    /* Floating Side Nav */
    .docs-nav {
        position: sticky;
        top: 90px;
        max-height: calc(100vh - 120px);
        overflow-y: auto;
        padding-right: 0.5rem;
    }
    .docs-nav::-webkit-scrollbar {
        width: 4px;
    }
    .docs-nav::-webkit-scrollbar-thumb {
        background: var(--api-border);
        border-radius: 4px;
    }
    .docs-nav .nav-link {
        font-size: 0.9rem;
        color: var(--api-text-muted);
        padding: 0.4rem 1rem;
        border-left: 2px solid transparent;
        transition: all 0.2s ease;
        font-weight: 500;
    }
    .docs-nav .nav-link:hover, .docs-nav .nav-link.active {
        color: var(--api-primary);
        border-left-color: var(--api-primary);
        background-color: rgba(99, 102, 241, 0.05);
        border-top-right-radius: 4px;
        border-bottom-right-radius: 4px;
    }
    .docs-nav-section {
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #475569;
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
        padding-left: 1rem;
    }

    /* Main Docs Content Layout */
    .api-card {
        border: 1px solid var(--api-border);
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
        margin-bottom: 2rem;
        background: #ffffff;
        overflow: hidden;
    }
    .api-card-header {
        border-bottom: 1px solid var(--api-border);
        padding: 1.5rem;
        background-color: #f8fafc;
    }

    /* Key Management Panel */
    .api-key-panel {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid var(--api-border);
        border-radius: 12px;
        padding: 1.5rem;
    }

    /* Method Badges */
    .method-badge {
        display: inline-block;
        padding: 0.25rem 0.6rem;
        font-size: 0.75rem;
        font-weight: 700;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .method-badge.post {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--api-success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    .method-badge.get {
        background-color: rgba(14, 165, 233, 0.1);
        color: var(--api-secondary);
        border: 1px solid rgba(14, 165, 233, 0.2);
    }

    .endpoint-url {
        font-family: 'Courier New', Courier, monospace;
        font-size: 0.85rem;
        font-weight: 600;
        background-color: #f1f5f9;
        color: #334155;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        word-break: break-all;
    }

    /* Table Styles */
    .table-params th {
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #475569;
        background-color: #f8fafc;
    }
    .table-params td {
        font-size: 0.875rem;
        vertical-align: middle;
    }
    .type-badge {
        font-size: 0.75rem;
        font-family: monospace;
        background-color: #f1f5f9;
        color: #64748b;
        padding: 0.15rem 0.35rem;
        border-radius: 4px;
    }
    .required-badge {
        font-size: 0.7rem;
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--api-danger);
        padding: 0.15rem 0.35rem;
        border-radius: 4px;
        font-weight: 600;
    }
    .optional-badge {
        font-size: 0.7rem;
        background-color: rgba(100, 116, 139, 0.1);
        color: var(--api-text-muted);
        padding: 0.15rem 0.35rem;
        border-radius: 4px;
        font-weight: 600;
    }

    /* Three-Panel Code Area */
    .code-panel {
        background-color: var(--api-dark-bg);
        border-radius: 12px;
        color: #f8fafc;
        overflow: hidden;
        margin-top: 1rem;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3);
    }
    .code-panel-header {
        background-color: #0b0f19;
        padding: 0.5rem 1rem;
        border-bottom: 1px solid #1e293b;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .code-tabs {
        display: flex;
        gap: 0.5rem;
    }
    .code-tab-btn {
        background: transparent;
        border: none;
        color: #94a3b8;
        font-size: 0.75rem;
        padding: 0.3rem 0.6rem;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .code-tab-btn.active, .code-tab-btn:hover {
        color: #f8fafc;
        background-color: #1e293b;
    }
    .code-panel-body {
        padding: 1.25rem;
        margin: 0;
        font-family: 'Courier New', Courier, monospace;
        font-size: 0.85rem;
        overflow-x: auto;
    }
    .code-panel-body pre {
        margin: 0;
        padding: 0;
        background: transparent;
        color: inherit;
    }
    .code-panel-body code {
        color: inherit;
    }

    /* Copy Button Inside Code Panel */
    .btn-copy-code {
        background: transparent;
        border: none;
        color: #94a3b8;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s ease;
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
    }
    .btn-copy-code:hover {
        color: #ffffff;
        background-color: #1e293b;
    }

    /* Error Codes Section */
    .error-code-row {
        transition: background-color 0.2s ease;
    }
    .error-code-row:hover {
        background-color: #f8fafc;
    }
</style>

<div class="api-container container-fluid py-4">
    <div class="row">
        <!-- Sidebar Navigation (Responsive and Sticky) -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="docs-nav">
                <div class="docs-nav-section">Getting Started</div>
                <a href="#authentication" class="nav-link active">Authentication</a>
                <a href="#response-codes" class="nav-link">Response Codes</a>

                <div class="docs-nav-section">SMS Services</div>
                <a href="#check-balance" class="nav-link">Check Balance</a>
                <a href="#send-sms" class="nav-link">Send Bulk SMS</a>
                <a href="#send-corporate-sms" class="nav-link">Send Corporate SMS</a>

                <div class="docs-nav-section">OTP Services</div>
                <a href="#generate-otp" class="nav-link">Generate & Send OTP</a>
                <a href="#send-pregenerated-otp" class="nav-link">Send Custom OTP</a>
                <a href="#verify-otp" class="nav-link">Verify OTP</a>

                <div class="docs-nav-section">Voice Services</div>
                <a href="#send-voice" class="nav-link">Voice TTS Call</a>
                <a href="#send-voice-audio" class="nav-link">Voice Audio Call</a>

                <div class="docs-nav-section">ID Management</div>
                <a href="#submit-senderid" class="nav-link">Register Sender ID</a>
                <a href="#check-senderid" class="nav-link">Sender ID Status</a>
                <a href="#submit-callerid" class="nav-link">Register Caller ID</a>
                <a href="#check-callerid" class="nav-link">Caller ID Status</a>
            </div>
        </div>

        <!-- Documentation Core Content -->
        <div class="col-lg-9 col-12">
            <!-- Header Banner -->
            <div class="api-card p-4 mb-4" style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); color: #ffffff;">
                <h2 class="fw-bold mb-2">Developer API Reference</h2>
                <p class="mb-0 opacity-80">Build custom messaging systems, send OTP verifications, verify voice caller IDs, and query transaction logs programmatically using our standardized API endpoints.</p>
            </div>

            <!-- Authentication Panel -->
            <div id="authentication" class="api-card">
                <div class="api-card-header">
                    <h5 class="fw-bold mb-1">Authentication</h5>
                    <p class="text-muted mb-0">Secure and verify your programmatic API queries.</p>
                </div>
                <div class="card-body">
                    <p>All API queries require authentication. You must include your private token parameter inside the body of every <code>POST</code> request.</p>
                    
                    <div class="api-key-panel my-3">
                        <?php
                        $status_stmt = $conn->prepare("SELECT api_access_status, api_key FROM users WHERE id = ?");
                        $status_stmt->bind_param("i", $user['id']);
                        $status_stmt->execute();
                        $api_user = $status_stmt->get_result()->fetch_assoc();
                        $status_stmt->close();

                        switch ($api_user['api_access_status']) {
                            case 'approved':
                                ?>
                                <label for="api_key" class="form-label fw-semibold text-dark">Your API Token Key</label>
                                <div id="api-key-status"></div>
                                <div class="input-group">
                                    <input type="text" id="api_key" class="form-control font-monospace border-end-0 bg-white" value="<?php echo htmlspecialchars($api_user['api_key']); ?>" readonly>
                                    <button class="btn btn-outline-secondary border-start-0" id="copyApiBtn"><i class="far fa-copy"></i> Copy</button>
                                    <button class="btn btn-outline-danger" id="regenerate-api-key"><i class="fas fa-sync-alt"></i> Regenerate</button>
                                </div>
                                <small class="form-text text-muted mt-2 d-block"><i class="fas fa-exclamation-triangle text-warning"></i> Keep your token confidential. Regenerating it will instantly disable any active software integrations using the previous key.</small>
                                <?php
                                break;
                            case 'requested':
                                ?>
                                <div class="alert alert-info d-flex align-items-center mb-0">
                                    <i class="fas fa-clock fa-lg me-3"></i>
                                    <div>
                                        <h6 class="alert-heading fw-bold mb-1">Access Request Pending Review</h6>
                                        <p class="mb-0 small">An administrator is currently reviewing your developer access request. You will receive an notification when access has been granted.</p>
                                    </div>
                                </div>
                                <?php
                                break;
                            case 'denied':
                                ?>
                                <div class="alert alert-danger d-flex flex-column align-items-start mb-0">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-times-circle fa-lg me-3"></i>
                                        <h6 class="alert-heading fw-bold mb-0">Developer Access Denied</h6>
                                    </div>
                                    <p class="small mb-3">Your developer access request was rejected. Please verify your profile info and submit a request again.</p>
                                    <button class="btn btn-warning btn-sm" id="requestApiAccessBtn">Request Access Again</button>
                                </div>
                                <?php
                                break;
                            case 'none':
                            default:
                                ?>
                                <div class="alert alert-warning d-flex flex-column align-items-start mb-0">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-lock fa-lg me-3"></i>
                                        <h6 class="alert-heading fw-bold mb-0">Developer Access Required</h6>
                                    </div>
                                    <p class="small mb-3">To begin making integration queries, you must submit a developer access request for review.</p>
                                    <button class="btn btn-primary btn-sm" id="requestApiAccessBtn">Request API Access</button>
                                </div>
                                <?php
                                break;
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- API Response & Error Codes -->
            <div id="response-codes" class="api-card">
                <div class="api-card-header">
                    <h5 class="fw-bold mb-1">Response Codes</h5>
                    <p class="text-muted mb-0">Understand the system response statuses.</p>
                </div>
                <div class="card-body">
                    <p>All successful transactions return JSON objects carrying a matching <code>"error_code": "000"</code> status. Review standard response formats below.</p>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-params">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Meaning</th>
                                    <th>Category</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="error-code-row">
                                    <td><code>000</code></td>
                                    <td>Success response. Billed actions successfully completed.</td>
                                    <td><span class="badge bg-success-subtle text-success">Successful</span></td>
                                </tr>
                                <tr class="error-code-row">
                                    <td><code>400</code></td>
                                    <td>Bad parameters or invalid request structure (e.g., empty phone lists).</td>
                                    <td><span class="badge bg-danger-subtle text-danger">Client Error</span></td>
                                </tr>
                                <tr class="error-code-row">
                                    <td><code>401</code></td>
                                    <td>Authentication failed. Invalid developer token key.</td>
                                    <td><span class="badge bg-danger-subtle text-danger">Client Error</span></td>
                                </tr>
                                <tr class="error-code-row">
                                    <td><code>405</code></td>
                                    <td>Invalid HTTP Request Method. Endpoint requires POST.</td>
                                    <td><span class="badge bg-danger-subtle text-danger">Client Error</span></td>
                                </tr>
                                <tr class="error-code-row">
                                    <td><code>107</code></td>
                                    <td>Insufficient Wallet Balance. Billed cost exceeds main balance.</td>
                                    <td><span class="badge bg-warning-subtle text-warning">Transaction</span></td>
                                </tr>
                                <tr class="error-code-row">
                                    <td><code>110</code></td>
                                    <td>Message contains restricted words from blocklists.</td>
                                    <td><span class="badge bg-warning-subtle text-warning">Validation</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <h3 class="fw-bold mb-4" id="endpoints-section">SMS Service Endpoints</h3>

            <!-- Check Wallet Balance -->
            <div id="check-balance" class="api-card">
                <div class="api-card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Check Wallet Balance</h5>
                    <div>
                        <span class="method-badge post">POST</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">Query current available cash balance in your main wallet.</p>
                    <div class="mb-3">
                        <span class="endpoint-url"><?php echo SITE_URL; ?>/api/balance.php</span>
                    </div>

                    <h6 class="fw-bold mt-4 mb-2">Request Body Parameters</h6>
                    <table class="table table-bordered table-params">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Constraint</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>token</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Your developer token key.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="code-panel">
                        <div class="code-panel-header">
                            <div class="code-tabs" data-target="panel-balance">
                                <button class="code-tab-btn active" data-lang="curl">cURL</button>
                                <button class="code-tab-btn" data-lang="php">PHP</button>
                                <button class="code-tab-btn" data-lang="json">Response</button>
                            </div>
                            <button class="btn-copy-code" onclick="copyCode(this)"><i class="far fa-copy"></i> Copy</button>
                        </div>
                        <div class="code-panel-body" id="panel-balance">
                            <div class="code-block curl active">
<pre><code>curl --location --request POST '<?php echo SITE_URL; ?>/api/balance.php' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--data-urlencode 'token=<?php echo htmlspecialchars($api_user['api_key'] ?? 'YOUR_API_KEY'); ?>'</code></pre>
                            </div>
                            <div class="code-block php d-none">
<pre><code>&lt;?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, '<?php echo SITE_URL; ?>/api/balance.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'token' => '<?php echo htmlspecialchars($api_user['api_key'] ?? 'YOUR_API_KEY'); ?>'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
print_r(json_decode($response, true));
?&gt;</code></pre>
                            </div>
                            <div class="code-block json d-none">
<pre><code>{
    "status": "success",
    "error_code": "000",
    "balance": "4720.50"
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Send Bulk SMS -->
            <div id="send-sms" class="api-card">
                <div class="api-card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Send Bulk SMS</h5>
                    <div>
                        <span class="method-badge post">POST</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">Sends standard promotional or transactional bulk text messages to recipients. Long texts (exceeding 160 characters) are automatically segmented and charged as multiple units.</p>
                    <div class="mb-3">
                        <span class="endpoint-url"><?php echo SITE_URL; ?>/api/sms.php</span>
                    </div>

                    <h6 class="fw-bold mt-4 mb-2">Request Body Parameters</h6>
                    <table class="table table-bordered table-params">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Constraint</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>token</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Your developer token key.</td>
                            </tr>
                            <tr>
                                <td><code>senderID</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Approved promotional sender ID. Max 11 alphanumeric characters.</td>
                            </tr>
                            <tr>
                                <td><code>recipients</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Comma-separated phone numbers (e.g. <code>2348012345678,2349087654321</code>).</td>
                            </tr>
                            <tr>
                                <td><code>message</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Message body. Billed dynamically at 1 unit/160 chars for first segment, and 1 unit/153 chars for subsequent.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="code-panel">
                        <div class="code-panel-header">
                            <div class="code-tabs" data-target="panel-sms">
                                <button class="code-tab-btn active" data-lang="curl">cURL</button>
                                <button class="code-tab-btn" data-lang="php">PHP</button>
                                <button class="code-tab-btn" data-lang="json">Response</button>
                            </div>
                            <button class="btn-copy-code" onclick="copyCode(this)"><i class="far fa-copy"></i> Copy</button>
                        </div>
                        <div class="code-panel-body" id="panel-sms">
                            <div class="code-block curl active">
<pre><code>curl --location --request POST '<?php echo SITE_URL; ?>/api/sms.php' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--data-urlencode 'token=<?php echo htmlspecialchars($api_user['api_key'] ?? 'YOUR_API_KEY'); ?>' \
--data-urlencode 'senderID=YOUR_SENDER_ID' \
--data-urlencode 'recipients=2348012345678,2348098765432' \
--data-urlencode 'message=Hello Developer, this is a test message from PhilmoreSMS API.'</code></pre>
                            </div>
                            <div class="code-block php d-none">
<pre><code>&lt;?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, '<?php echo SITE_URL; ?>/api/sms.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'token' => '<?php echo htmlspecialchars($api_user['api_key'] ?? 'YOUR_API_KEY'); ?>',
    'senderID' => 'YOUR_SENDER_ID',
    'recipients' => '2348012345678',
    'message' => 'Hello Developer, this is a test message from PhilmoreSMS API.'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
print_r(json_decode($response, true));
?&gt;</code></pre>
                            </div>
                            <div class="code-block json d-none">
<pre><code>{
    "status": "success",
    "error_code": "000",
    "message": "Message sent successfully.",
    "units": 1,
    "pages": 1,
    "balance": "4715.50"
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Send Corporate SMS -->
            <div id="send-corporate-sms" class="api-card">
                <div class="api-card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Send Corporate SMS</h5>
                    <div>
                        <span class="method-badge post">POST</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">Sends messages via the Corporate route, bypasses standard DND limits. Ideal for transactional verifications and account notifications.</p>
                    <div class="mb-3">
                        <span class="endpoint-url"><?php echo SITE_URL; ?>/api/corporate.php</span>
                    </div>

                    <h6 class="fw-bold mt-4 mb-2">Request Body Parameters</h6>
                    <table class="table table-bordered table-params">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Constraint</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>token</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Your developer token key.</td>
                            </tr>
                            <tr>
                                <td><code>senderID</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Approved corporate sender ID.</td>
                            </tr>
                            <tr>
                                <td><code>recipients</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Comma-separated phone list.</td>
                            </tr>
                            <tr>
                                <td><code>message</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Message body. Dynamic page segments calculation applies.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="code-panel">
                        <div class="code-panel-header">
                            <div class="code-tabs" data-target="panel-corporate">
                                <button class="code-tab-btn active" data-lang="curl">cURL</button>
                                <button class="code-tab-btn" data-lang="php">PHP</button>
                                <button class="code-tab-btn" data-lang="json">Response</button>
                            </div>
                            <button class="btn-copy-code" onclick="copyCode(this)"><i class="far fa-copy"></i> Copy</button>
                        </div>
                        <div class="code-panel-body" id="panel-corporate">
                            <div class="code-block curl active">
<pre><code>curl --location --request POST '<?php echo SITE_URL; ?>/api/corporate.php' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--data-urlencode 'token=<?php echo htmlspecialchars($api_user['api_key'] ?? 'YOUR_API_KEY'); ?>' \
--data-urlencode 'senderID=CORP_SENDER' \
--data-urlencode 'recipients=2348012345678' \
--data-urlencode 'message=Dear Customer, your transaction alert is ready.'</code></pre>
                            </div>
                            <div class="code-block php d-none">
<pre><code>&lt;?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, '<?php echo SITE_URL; ?>/api/corporate.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'token' => '<?php echo htmlspecialchars($api_user['api_key'] ?? 'YOUR_API_KEY'); ?>',
    'senderID' => 'CORP_SENDER',
    'recipients' => '2348012345678',
    'message' => 'Dear Customer, your transaction alert is ready.'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
print_r(json_decode($response, true));
?&gt;</code></pre>
                            </div>
                            <div class="code-block json d-none">
<pre><code>{
    "status": "success",
    "error_code": "000",
    "message": "Corporate SMS sent successfully.",
    "units": 1,
    "balance": "4710.00"
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <h3 class="fw-bold mb-4">OTP Service Endpoints</h3>

            <!-- Generate and Send OTP -->
            <div id="generate-otp" class="api-card">
                <div class="api-card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Generate & Send OTP</h5>
                    <div>
                        <span class="method-badge post">POST</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">Generates a secure numeric or alphanumeric OTP code, templates it, and sends it directly to a phone number. Standardized verification ID is returned for verifying later.</p>
                    <div class="mb-3">
                        <span class="endpoint-url"><?php echo SITE_URL; ?>/api/sendotp.php</span>
                    </div>

                    <h6 class="fw-bold mt-4 mb-2">Request Body Parameters</h6>
                    <table class="table table-bordered table-params">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Constraint</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>token</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Your developer token key.</td>
                            </tr>
                            <tr>
                                <td><code>senderID</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Approved promotional sender ID.</td>
                            </tr>
                            <tr>
                                <td><code>recipients</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Single recipient phone number.</td>
                            </tr>
                            <tr>
                                <td><code>appnamecode</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>The brand/application display label.</td>
                            </tr>
                            <tr>
                                <td><code>templatecode</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>The registration template ID.</td>
                            </tr>
                            <tr>
                                <td><code>otp_type</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="optional-badge">Optional</span></td>
                                <td>Defaults to <code>NUMERIC</code>. (Can use <code>ALPHANUMERIC</code>).</td>
                            </tr>
                            <tr>
                                <td><code>otp_length</code></td>
                                <td><span class="type-badge">int</span></td>
                                <td><span class="optional-badge">Optional</span></td>
                                <td>Defaults to <code>6</code>. Length constraint.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="code-panel">
                        <div class="code-panel-header">
                            <div class="code-tabs" data-target="panel-gen-otp">
                                <button class="code-tab-btn active" data-lang="curl">cURL</button>
                                <button class="code-tab-btn" data-lang="json">Response</button>
                            </div>
                            <button class="btn-copy-code" onclick="copyCode(this)"><i class="far fa-copy"></i> Copy</button>
                        </div>
                        <div class="code-panel-body" id="panel-gen-otp">
                            <div class="code-block curl active">
<pre><code>curl --location --request POST '<?php echo SITE_URL; ?>/api/sendotp.php' \
--header 'Content-Type: application/x-www-form-urlencoded' \
--data-urlencode 'token=<?php echo htmlspecialchars($api_user['api_key'] ?? 'YOUR_API_KEY'); ?>' \
--data-urlencode 'senderID=OTP_SENDER' \
--data-urlencode 'recipients=2348012345678' \
--data-urlencode 'appnamecode=MyMobileApp' \
--data-urlencode 'templatecode=APP_OTP_TEMP'</code></pre>
                            </div>
                            <div class="code-block json d-none">
<pre><code>{
    "status": "success",
    "error_code": "000",
    "verification_id": "v_749320_dfba843",
    "cost": 5.0,
    "msg": "OTP sent successfully."
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Send Pre-Generated OTP -->
            <div id="send-pregenerated-otp" class="api-card">
                <div class="api-card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Send Pre-Generated OTP</h5>
                    <div>
                        <span class="method-badge post">POST</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">Sends an OTP code generated inside your own database structure using an approved template.</p>
                    <div class="mb-3">
                        <span class="endpoint-url"><?php echo SITE_URL; ?>/api/send_otp.php</span>
                    </div>

                    <h6 class="fw-bold mt-4 mb-2">Request Body Parameters</h6>
                    <table class="table table-bordered table-params">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Constraint</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>token</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Your developer token key.</td>
                            </tr>
                            <tr>
                                <td><code>senderID</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Approved sender ID.</td>
                            </tr>
                            <tr>
                                <td><code>recipients</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Recipient phone number.</td>
                            </tr>
                            <tr>
                                <td><code>otp</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Your custom pre-generated code.</td>
                            </tr>
                            <tr>
                                <td><code>templatecode</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Approved OTP template identifier.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Verify OTP -->
            <div id="verify-otp" class="api-card">
                <div class="api-card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Verify OTP</h5>
                    <div>
                        <span class="method-badge post">POST</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">Validates a verification code against the dynamic matching token in the gateway.</p>
                    <div class="mb-3">
                        <span class="endpoint-url"><?php echo SITE_URL; ?>/api/verifyotp.php</span>
                    </div>

                    <h6 class="fw-bold mt-4 mb-2">Request Body Parameters</h6>
                    <table class="table table-bordered table-params">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Constraint</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>token</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Your developer token key.</td>
                            </tr>
                            <tr>
                                <td><code>verification_id</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>The <code>verification_id</code> returned when generating the OTP.</td>
                            </tr>
                            <tr>
                                <td><code>otp</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>The code provided by the user.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <hr class="my-5">

            <h3 class="fw-bold mb-4">Voice Service Endpoints</h3>

            <!-- Send Voice TTS Call -->
            <div id="send-voice" class="api-card">
                <div class="api-card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Voice TTS Call</h5>
                    <div>
                        <span class="method-badge post">POST</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">Converts raw text scripts to speech (TTS) and triggers outbound calls to recipient lines.</p>
                    <div class="mb-3">
                        <span class="endpoint-url"><?php echo SITE_URL; ?>/api/voice.php</span>
                    </div>

                    <h6 class="fw-bold mt-4 mb-2">Request Body Parameters</h6>
                    <table class="table table-bordered table-params">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Constraint</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>token</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Your developer token key.</td>
                            </tr>
                            <tr>
                                <td><code>callerID</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>An approved phone number registered in your Caller ID panel.</td>
                            </tr>
                            <tr>
                                <td><code>recipients</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Outbound numbers. Comma-separated list.</td>
                            </tr>
                            <tr>
                                <td><code>message</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Plaintext content script to speech.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Send Voice Audio Call -->
            <div id="send-voice-audio" class="api-card">
                <div class="api-card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Voice Audio Call</h5>
                    <div>
                        <span class="method-badge post">POST</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">Executes outbound automated calls playing a pre-recorded audio file directly over the call stream.</p>
                    <div class="mb-3">
                        <span class="endpoint-url"><?php echo SITE_URL; ?>/api/voice_audio.php</span>
                    </div>

                    <h6 class="fw-bold mt-4 mb-2">Request Body Parameters</h6>
                    <table class="table table-bordered table-params">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Constraint</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>token</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Your developer token key.</td>
                            </tr>
                            <tr>
                                <td><code>callerID</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>An approved phone number registered in your Caller ID panel.</td>
                            </tr>
                            <tr>
                                <td><code>recipients</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Recipient lines. Comma-separated list.</td>
                            </tr>
                            <tr>
                                <td><code>audio</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>A public HTTP/HTTPS URL path pointing to a valid `.mp3` audio file.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <hr class="my-5">

            <h3 class="fw-bold mb-4">Sender & Caller ID Endpoints</h3>

            <!-- Submit Sender ID -->
            <div id="submit-senderid" class="api-card">
                <div class="api-card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Register Sender ID</h5>
                    <div>
                        <span class="method-badge post">POST</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">Submits a promotional alphanumeric sender ID for review by the platform administrators.</p>
                    <div class="mb-3">
                        <span class="endpoint-url"><?php echo SITE_URL; ?>/api/senderID.php</span>
                    </div>

                    <h6 class="fw-bold mt-4 mb-2">Request Body Parameters</h6>
                    <table class="table table-bordered table-params">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Constraint</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>token</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Your developer token key.</td>
                            </tr>
                            <tr>
                                <td><code>senderID</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td> Alphanumeric sender ID label (Max 11 chars).</td>
                            </tr>
                            <tr>
                                <td><code>message</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Sample message body you want to send using this ID.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Check Sender ID Status -->
            <div id="check-senderid" class="api-card">
                <div class="api-card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Check Sender ID Status</h5>
                    <div>
                        <span class="method-badge post">POST</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">Queries the approval status (`pending`, `approved`, or `rejected`) of a submitted sender ID.</p>
                    <div class="mb-3">
                        <span class="endpoint-url"><?php echo SITE_URL; ?>/api/check_senderID.php</span>
                    </div>
                </div>
            </div>

            <!-- Submit Caller ID -->
            <div id="submit-callerid" class="api-card">
                <div class="api-card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Register Caller ID</h5>
                    <div>
                        <span class="method-badge post">POST</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">Submits a new phone number to be approved as a Caller ID for Voice calls.</p>
                    <div class="mb-3">
                        <span class="endpoint-url"><?php echo SITE_URL; ?>/api/callerID.php</span>
                    </div>

                    <h6 class="fw-bold mt-4 mb-2">Request Body Parameters</h6>
                    <table class="table table-bordered table-params">
                        <thead>
                            <tr>
                                <th>Parameter</th>
                                <th>Type</th>
                                <th>Constraint</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>token</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Your developer token key.</td>
                            </tr>
                            <tr>
                                <td><code>callerID</code></td>
                                <td><span class="type-badge">string</span></td>
                                <td><span class="required-badge">Required</span></td>
                                <td>Outbound numeric phone number.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Check Caller ID Status -->
            <div id="check-callerid" class="api-card">
                <div class="api-card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Check Caller ID Status</h5>
                    <div>
                        <span class="method-badge post">POST</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">Queries the approval status (`pending`, `approved`, or `rejected`) of a Caller ID request.</p>
                    <div class="mb-3">
                        <span class="endpoint-url"><?php echo SITE_URL; ?>/api/check_callerID.php</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const requestBtn = document.getElementById('requestApiAccessBtn');
    const copyBtn = document.getElementById('copyApiBtn');
    const regenerateBtn = document.getElementById('regenerate-api-key');

    // Tabbed Code Block Switcher
    const codeTabBtns = document.querySelectorAll('.code-tab-btn');
    codeTabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabsContainer = this.closest('.code-tabs');
            const targetId = tabsContainer.dataset.target;
            const targetBlock = document.getElementById(targetId);
            const targetLang = this.dataset.lang;

            // Remove active from sibling buttons in this container
            tabsContainer.querySelectorAll('.code-tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Hide/Show matching blocks in code area
            targetBlock.querySelectorAll('.code-block').forEach(block => {
                if (block.classList.contains(targetLang)) {
                    block.classList.remove('d-none');
                    block.classList.add('active');
                } else {
                    block.classList.add('d-none');
                    block.classList.remove('active');
                }
            });
        });
    });

    if (requestBtn) {
        requestBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';

            fetch('ajax/request_api_access.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const container = requestBtn.closest('.alert');
                    container.classList.remove('alert-warning', 'alert-danger');
                    container.classList.add('alert-info');
                    container.innerHTML = `
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clock fa-lg me-3"></i>
                            <div>
                                <h6 class="alert-heading fw-bold mb-1">Access Request Pending Review</h6>
                                <p class="mb-0 small">An administrator is currently reviewing your developer access request. You will receive an notification when access has been granted.</p>
                            </div>
                        </div>`;
                } else {
                    alert('Error: ' + data.message);
                    requestBtn.disabled = false;
                    requestBtn.textContent = 'Request API Access';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                requestBtn.disabled = false;
                requestBtn.textContent = 'Request API Access';
            });
        });
    }

    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const apiKeyInput = document.getElementById('api_key');
            navigator.clipboard.writeText(apiKeyInput.value).then(() => {
                const originalHtml = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check text-success"></i> Copied!';
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                }, 2000);
            }, (err) => {
                alert('Failed to copy API key.');
            });
        });
    }

    if (regenerateBtn) {
        regenerateBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to regenerate your API key? Your old key will stop working immediately.')) {
                fetch('ajax/regenerate_api_key.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    const statusDiv = document.getElementById('api-key-status');
                    if (data.success) {
                        document.getElementById('api_key').value = data.api_key;
                        statusDiv.innerHTML = '<div class="alert alert-success mt-3 small py-2"><i class="fas fa-check-circle"></i> New API key generated successfully!</div>';
                    } else {
                        statusDiv.innerHTML = '<div class="alert alert-danger mt-3 small py-2"><i class="fas fa-times-circle"></i> ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const statusDiv = document.getElementById('api-key-status');
                    statusDiv.innerHTML = '<div class="alert alert-danger mt-3 small py-2"><i class="fas fa-exclamation-triangle"></i> An error occurred while regenerating the key.</div>';
                });
            }
        });
    }

    // Scrollspy Highlight for Left Navigation links
    const sections = document.querySelectorAll('.api-card');
    const navLinks = document.querySelectorAll('.docs-nav .nav-link');

    window.addEventListener('scroll', () => {
        let currentSection = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (window.scrollY >= sectionTop - 120) {
                currentSection = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${currentSection}`) {
                link.classList.add('active');
            }
        });
    });
});

// Copy code text helper
function copyCode(btn) {
    const codePanel = btn.closest('.code-panel');
    const activeBlock = codePanel.querySelector('.code-block.active code');
    if (activeBlock) {
        navigator.clipboard.writeText(activeBlock.innerText).then(() => {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check text-success"></i> Copied!';
            setTimeout(() => {
                btn.innerHTML = originalHtml;
            }, 2000);
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>