<?php
/**
 * api/add_customer.php
 * -----------------------------------------------------------------
 * Ginagamit ito ng ADD CUSTOMER button.
 * Nagreregister ng BAGONG customer at sini-save sa database,
 * kahit wala pang order/bill.
 *
 * Dalawa lang ang kailangan:
 *   - Customer Name
 *   - Contact Number
 *
 * Wala nang Order Number dito — automatic na iyon tuwing may bill.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/bootstrap.php';

// ---- Kunin ang datos mula sa webpage ----
$input = json_input();

$name    = isset($input['name'])    ? trim($input['name'])    : '';
$contact = isset($input['contact']) ? trim($input['contact']) : '';

// ---- Dapat kompleto ang dalawang field ----
if ($name == '' || $contact == '') {
    json_response(array(
        'ok'    => false,
        'error' => 'Please enter the Customer Name and Contact Number.'
    ), 422);
}

$customerModel = new Customer(db());

// ---- Rehistrado na ba? (parehong pangalan at contact) ----
$existing = $customerModel->findByNameAndContact($name, $contact);
if ($existing != null) {
    json_response(array(
        'ok'       => false,
        'error'    => $name . ' is already registered with that contact number.',
        'customer' => $existing
    ), 409);
}

// ---- I-save ang bagong customer ----
$customerId = $customerModel->save($name, $contact);

// ---- Ibalik ang bagong record ----
json_response(array(
    'ok'       => true,
    'message'  => 'New customer registered successfully.',
    'customer' => array(
        'customer_id'    => $customerId,
        'customer_name'  => $name,
        'contact_number' => $contact
    )
));
