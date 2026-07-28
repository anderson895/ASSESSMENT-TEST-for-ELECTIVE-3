<?php
/**
 * api/save_order.php
 * -----------------------------------------------------------------
 * Ginagamit ito ng BILL button.
 *
 * Ito ang gumagawa ng lahat:
 *   1. Kinakalkula ang bill (discount, 12% VAT, sukli)
 *   2. Sinusuri kung sapat ang stock at ang bayad
 *   3. Sini-save ang customer at ang order
 *   4. Gumagawa ng AUTOMATIC na order number
 *   5. Binabawasan ang stock ng mga nabiling produkto
 *   6. Ibinabalik ang RESIBO na handa nang i-print
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/bootstrap.php';

// ---- Kunin ang datos mula sa webpage ----
$input    = json_input();
$customer = input_customer($input);

$cart = isset($input['cart']) ? $input['cart'] : array();

// ---- Dapat may binili man lang ----
if (!is_array($cart) || count($cart) == 0) {
    json_response(array(
        'ok'    => false,
        'error' => 'Nothing to bill. Enter at least one quantity.'
    ), 422);
}

// ---- Ang PANGALAN lang ang kailangan (optional ang contact number) ----
if ($customer['name'] == '') {
    json_response(array(
        'ok'    => false,
        'error' => 'Please enter the Customer Name before billing.'
    ), 422);
}

$pdo = db();

// ---- Dapat may napiling cashier (siya ang lalabas sa resibo) ----
$cashier = input_cashier($pdo, $input);

if ($cashier == null) {
    json_response(array(
        'ok'    => false,
        'error' => 'Please select the Cashier on Duty before billing.'
    ), 422);
}

// ---- 1) Kalkulahin ang bill ----
$bill = build_bill($pdo, $input);

if ($bill->grandTotal() <= 0) {
    json_response(array(
        'ok'    => false,
        'error' => 'All quantities are zero.'
    ), 422);
}

// ---- 2) Sapat ba ang stock? ----
$problems = $bill->stockProblems();

if (count($problems) > 0) {
    $messages = array();

    foreach ($problems as $problem) {
        $messages[] = $problem['product_name']
                    . ' (asked ' . $problem['requested']
                    . ', only ' . $problem['stock'] . ' left)';
    }

    json_response(array(
        'ok'    => false,
        'error' => 'Not enough stock for: ' . implode('; ', $messages) . '.'
    ), 409);
}

// ---- 3) Sapat ba ang bayad? ----
if (!$bill->isPaid()) {
    json_response(array(
        'ok'    => false,
        'error' => 'Amount Received (₱' . number_format($bill->amountReceived(), 2)
                 . ') is less than the Grand Total (₱' . number_format($bill->grandTotal(), 2) . ').'
    ), 422);
}

// ---- 4) I-save ang customer (o gamitin ang dati niyang record) ----
$customerModel = new Customer($pdo);
$customerId    = $customerModel->save($customer['name'], $customer['contact']);

// ---- 5) I-save ang order (dito ginagawa ang automatic na order number) ----
$orderModel = new Order($pdo);

try {
    $saved = $orderModel->save($customerId, $cashier['employee_id'], $bill);
} catch (RuntimeException $e) {
    // Halimbawa: naubos ang stock habang nagbi-bill.
    json_response(array('ok' => false, 'error' => $e->getMessage()), 409);
}

// ---- 6) Buuin ang resibo gamit ang TOTOONG order number ----
$receipt = new Receipt(
    store(),
    $bill,
    $customer,
    $cashier,
    $saved['order_number']
);

// ---- 7) Ibalik ang resulta sa webpage ----
json_response(array(
    'ok'           => true,
    'order_id'     => $saved['order_id'],
    'order_number' => $saved['order_number'],
    'customer_id'  => $customerId,
    'summary'      => $bill->summary(),
    'receipt_html' => $receipt->html(false)
));
