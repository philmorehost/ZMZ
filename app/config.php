<?php
// app/config.php
// --- Database Configuration ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'appsms_portal');       // Updated based on install.sql
define('DB_USERNAME', 'appsms_portal');      // REPLACE WITH ACTUAL DB USERNAME
define('DB_PASSWORD', '1122@EBEN.COM');          // REPLACE WITH ACTUAL DB PASSWORD

// --- API & Security Configuration ---
// WARNING: Please ensure the default administrator password is changed immediately after installation.
define('JWT_SECRET', 'd86f7b11c9db4c8e7456d95ff0d1a49f1391cb0bbadfe2cf8e811c79a5b678f1');

// Note: SITE_URL and other constants are auto-detected in bootstrap.php
?>
