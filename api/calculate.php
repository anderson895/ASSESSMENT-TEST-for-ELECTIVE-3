<?php
/**
 * api/calculate.php
 * -----------------------------------------------------------------
 * Ginagamit ito ng TOTAL button.
 *
 * Kinakalkula ang total ng bawat kategorya, ang discount, ang 12% VAT,
 * ang grand total, at ang SUKLI. Hindi pa ito nagsi-save sa database.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/bootstrap.php';

// Kunin ang datos mula sa webpage.
$input = json_input();

if (isset($input['cart']) && !is_array($input['cart'])) {
    json_response(array('ok' => false, 'error' => 'Invalid cart.'), 422);
}

// Kalkulahin gamit ang Bill class (presyo galing mismo sa database).
$bill = build_bill(db(), $input);

// Ibalik ang resulta sa webpage.
json_response(array(
    'ok'      => true,
    'summary' => $bill->summary()
));
