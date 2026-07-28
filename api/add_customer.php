<?php
/**
 * api/add_customer.php
 * -----------------------------------------------------------------
 * Ginagamit ito ng ADD CUSTOMER button.
 * Nagreregister ng BAGONG customer at sini-save sa database,
 * kahit wala pang order/bill.
 *
 * Isa lang ang talagang kailangan:
 *   - Customer Name        (kailangan)
 *   - Contact Number       (OPTIONAL — puwedeng walang numero ang walk-in)
 *
 * Wala nang Order Number dito — automatic na iyon tuwing may bill.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/bootstrap.php';

// ---- Kunin ang datos mula sa webpage ----
$input = json_input();

$name    = isset($input['name'])    ? trim($input['name'])    : '';
$contact = isset($input['contact']) ? trim($input['contact']) : '';

// ---- Ang pangalan lang ang kailangan ----
if ($name == '') {
    json_response(array(
        'ok'    => false,
        'error' => 'Please enter the Customer Name.'
    ), 422);
}

$customerModel = new Customer(db());

// ---- Rehistrado na ba? (parehong pangalan at contact) ----
$existing = $customerModel->findByNameAndContact($name, $contact);
if ($existing != null) {
    $reason = ($contact == '')
        ? $name . ' is already registered as a walk-in (no contact number).'
        : $name . ' is already registered with that contact number.';

    json_response(array(
        'ok'       => false,
        'error'    => $reason,
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
