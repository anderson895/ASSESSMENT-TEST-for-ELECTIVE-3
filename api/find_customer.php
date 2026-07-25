<?php
/**
 * api/find_customer.php
 * -----------------------------------------------------------------
 * Ginagamit ito ng FIND button.
 * Tumatanggap ng Contact Number o Order Number, tapos hinahanap
 * ang customer sa database.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/bootstrap.php';

// Kunin ang hinahanap mula sa webpage.
$input   = json_input();
$keyword = '';
if (isset($input['keyword'])) {
    $keyword = trim($input['keyword']);
}

// Wala palang inilagay.
if ($keyword == '') {
    json_response(array(
        'ok'    => false,
        'error' => 'Please enter a Contact Number or Order Number.'
    ), 422);
}

// Hanapin ang customer gamit ang Customer class.
$customerModel = new Customer(db());
$customer      = $customerModel->find($keyword);

// Walang nakita.
if ($customer == null) {
    json_response(array(
        'ok'    => false,
        'error' => 'No customer found for "' . $keyword . '".'
    ), 404);
}

// May nakita - ibalik ang impormasyon niya.
json_response(array(
    'ok'       => true,
    'customer' => $customer
));
