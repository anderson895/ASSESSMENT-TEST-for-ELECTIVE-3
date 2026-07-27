<?php
/**
 * api/find_customer.php
 * -----------------------------------------------------------------
 * Ginagamit ito ng FIND button.
 * Tumatanggap ng Contact Number o Order Number, tapos hinahanap
 * ang customer sa database.
 *
 * Bukod sa detalye ng customer, ibinabalik din nito ang LAHAT ng
 * dati/lumang order niya (order history).
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
$pdo           = db();
$customerModel = new Customer($pdo);
$customer      = $customerModel->find($keyword);

// Walang nakita.
if ($customer == null) {
    json_response(array(
        'ok'    => false,
        'error' => 'No customer found for "' . $keyword . '".'
    ), 404);
}

// May nakita - kunin din ang mga lumang order niya.
$orderModel = new Order($pdo);
$history    = $orderModel->historyByCustomer($customer['customer_id']);

// Ibalik ang impormasyon niya kasama ang order history.
json_response(array(
    'ok'       => true,
    'customer' => $customer,
    'orders'   => $history
));
