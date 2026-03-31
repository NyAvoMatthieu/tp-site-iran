<?php
/* Entry point – redirect to dashboard */
require_once __DIR__ . '/includes/auth.php';

header('Location: ' . admin_login_url());
exit;
