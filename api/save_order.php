<?php
/**
 * api/save_order.php
 * -----------------------------------------------------------------
 * Ginagamit ito ng BILL button.
 * Kinakalkula ang bill AT sini-save ang order sa database.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/bootstrap.php';

// ---- Kunin ang datos mula sa webpage ----
$input = json_input();

$customer = array();
if (isset($input['customer'])) {
    $customer = $input['customer'];
}

$cart = array();
if (isset($input['cart'])) {
    $cart = $input['cart'];
}

// ---- Dapat may binili man lang ----
if (!is_array($cart) || count($cart) == 0) {
    json_response(array(
        'ok'    => false,
        'error' => 'Nothing to bill. Enter at least one quantity.'
    ), 422);
}

// ---- Kunin ang detalye ng customer ----
$name    = '';
$contact = '';
$orderNo = '';

if (isset($customer['name']))         { $name    = trim($customer['name']); }
if (isset($customer['contact']))      { $contact = trim($customer['contact']); }
if (isset($customer['order_number'])) { $orderNo = trim($customer['order_number']); }

// Kailangang kompleto ang tatlo. Ang Order Number ay manu-manong inilalagay.
if ($name == '' || $contact == '' || $orderNo == '') {
    json_response(array(
        'ok'    => false,
        'error' => 'Please enter the Customer Name, Contact Number, and Order Number before billing.'
    ), 422);
}

$pdo = db();

// ---- 1) Kalkulahin ang bill ----
$productModel = new Product($pdo);
$bill         = new Bill($productModel->priceMap());
$bill->load($cart);

if ($bill->grandTotal() <= 0) {
    json_response(array(
        'ok'    => false,
        'error' => 'All quantities are zero.'
    ), 422);
}

// ---- 2) I-save ang customer (o gamitin ang dati niyang record) ----
$customerModel = new Customer($pdo);

try {
    $customerId = $customerModel->save($name, $contact, $orderNo);
} catch (RuntimeException $e) {
    // Halimbawa: gamit na ng ibang tao ang Order Number.
    json_response(array('ok' => false, 'error' => $e->getMessage()), 409);
}

// ---- 3) I-save ang order ----
$orderModel = new Order($pdo);
$orderId    = $orderModel->save($customerId, $bill);

// ---- 4) Ibalik ang resulta sa webpage ----
json_response(array(
    'ok'          => true,
    'order_id'    => $orderId,
    'customer_id' => $customerId,
    'summary'     => $bill->summary()
));
