<?php
/**
 * api/save_order.php
 * POST {
 *   "customer": { "name", "contact", "order_number" },
 *   "cart": [ {product_id, category, quantity}, ... ]
 * }
 * -> Saves the customer (if new) + order + order_items, then returns the
 *    persisted order id and bill summary. Used by the Bill / Print flow.
 */
require_once __DIR__ . '/../config/bootstrap.php';

$input    = json_input();
$cust     = $input['customer'] ?? [];
$cart     = $input['cart'] ?? [];

if (!is_array($cart) || count($cart) === 0) {
    json_response(['ok' => false, 'error' => 'Nothing to bill. Enter at least one quantity.'], 422);
}

// Require real customer details so we never store blank "Walk-in Customer" rows.
// Order Number is entered manually (per the given design / sample layout).
$custName    = trim((string) ($cust['name'] ?? ''));
$custContact = trim((string) ($cust['contact'] ?? ''));
$custOrderNo = trim((string) ($cust['order_number'] ?? ''));
if ($custName === '' || $custContact === '' || $custOrderNo === '') {
    json_response([
        'ok'    => false,
        'error' => 'Please enter the Customer Name, Contact Number, and Order Number before billing.',
    ], 422);
}

$pdo = db();

$product = new Product($pdo);
$bill    = new Bill($product->priceMap());
$bill->load($cart);

if ($bill->grandTotal() <= 0) {
    json_response(['ok' => false, 'error' => 'All quantities are zero.'], 422);
}

// Save / reuse the customer.
$customerModel = new Customer($pdo);
$customerId = $customerModel->save(
    (string) ($cust['name'] ?? ''),
    (string) ($cust['contact'] ?? ''),
    (string) ($cust['order_number'] ?? '')
);

// Persist the order.
$orderModel = new Order($pdo);
$orderId = $orderModel->save($customerId, $bill);

json_response([
    'ok'          => true,
    'order_id'    => $orderId,
    'customer_id' => $customerId,
    'summary'     => $bill->summary(),
]);
