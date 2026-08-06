<?php
// Khalti requires each developer to register their own free sandbox merchant
// account and use their own personal test secret key — there is no shared
// public test key like eSewa provides.
//
// Get yours:
// 1. Sign up at https://test-admin.khalti.com/#/join/merchant
// 2. Login OTP is always: 987654
// 3. Copy your "Live Secret Key" from the dashboard (this is still the
//    sandbox key since you're on test-admin.khalti.com, not admin.khalti.com)
// 4. Paste it below, replacing the placeholder.

define('KHALTI_SECRET_KEY', 'PASTE_YOUR_OWN_TEST_SECRET_KEY_HERE');

// Khalti's sandbox API base — do not change these
define('KHALTI_INITIATE_URL', 'https://dev.khalti.com/api/v2/epayment/initiate/');
define('KHALTI_LOOKUP_URL', 'https://dev.khalti.com/api/v2/epayment/lookup/');

// Where Khalti redirects the browser back to after payment
define('KHALTI_RETURN_URL', 'http://localhost/food_ordering_system/user/khalti_callback.php');
define('KHALTI_WEBSITE_URL', 'http://localhost/food_ordering_system/');