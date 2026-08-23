<?php
// eSewa TEST/SANDBOX credentials — these are eSewa's officially published
// developer test values, not real merchant credentials. Safe to use for
// academic/demo projects. No real money is ever involved with these.

define('ESEWA_MERCHANT_CODE', 'EPAYTEST');
define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q');

// Test payment form endpoint (sandbox)
define('ESEWA_PAYMENT_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form');

// Test transaction status verification endpoint (sandbox)
define('ESEWA_STATUS_URL', 'https://rc.esewa.com.np/api/epay/transaction/status/');

// Where eSewa redirects the browser back to after payment
if (!defined('APP_URL')) {
    require_once __DIR__ . '/config.php';
}
define('ESEWA_SUCCESS_URL', APP_URL . '/user/esewa_success.php');
define('ESEWA_FAILURE_URL', APP_URL . '/user/esewa_failure.php');