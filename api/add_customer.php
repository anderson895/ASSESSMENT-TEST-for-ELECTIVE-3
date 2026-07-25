<?php
/**
 * api/add_customer.php
 * -----------------------------------------------------------------
 * Ginagamit ito ng ADD CUSTOMER button.
 * Nagreregister ng BAGONG customer at sini-save sa database,
 * kahit wala pang order/bill. Ang tatlong detalye ay required:
 *   - Customer Name
 *   - Contact Number
 *   - Order Number  (dapat hindi pa gamit ng iba)
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/bootstrap.php';

// ---- Kunin ang datos mula sa webpage ----
$input = json_input();

$name    = isset($input['name'])         ? trim($input['name'])         : '';
$contact = isset($input['contact'])      ? trim($input['contact'])      : '';
$orderNo = isset($input['order_number']) ? trim($input['order_number']) : '';

// ---- Dapat kompleto ang tatlong field ----
if ($name == '' || $contact == '' || $orderNo == '') {
    json_response(array(
        'ok'    => false,
        'error' => 'Please enter the Customer Name, Contact Number, and Order Number.'
    ), 422);
}

$customerModel = new Customer(db());

// ---- Rehistrado na ba? (parehong pangalan at contact) ----
$existing = $customerModel->findByNameAndContact($name, $contact);
if ($existing != null) {
    json_response(array(
        'ok'       => false,
        'error'    => $name . ' is already registered (Order No: ' . $existing['order_number'] . ').',
        'customer' => $existing
    ), 409);
}

// ---- I-save ang bagong customer ----
try {
    $customerId = $customerModel->save($name, $contact, $orderNo);
} catch (RuntimeException $e) {
    // Halimbawa: gamit na ng ibang tao ang Order Number.
    json_response(array('ok' => false, 'error' => $e->getMessage()), 409);
}

// ---- Ibalik ang bagong record ----
json_response(array(
    'ok'       => true,
    'message'  => 'New customer registered successfully.',
    'customer' => array(
        'customer_id'    => $customerId,
        'customer_name'  => $name,
        'contact_number' => $contact,
        'order_number'   => $orderNo
    )
));
