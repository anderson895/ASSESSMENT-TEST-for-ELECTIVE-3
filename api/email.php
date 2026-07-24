<?php
/**
 * api/email.php
 * POST {
 *   "to": "customer@email.com",           (optional)
 *   "customer": { name, contact, order_number },
 *   "cart": [ {product_id, category, quantity}, ... ]
 * }
 * -> Builds the billing details as a plain-text body. If a recipient and a
 *    working mail server are available it attempts to send; otherwise it
 *    just returns the body so the UI can display it (per the spec, E-Mail may
 *    "display OR send" the billing details).
 */
require_once __DIR__ . '/../config/bootstrap.php';

$input = json_input();
$to    = trim((string) ($input['to'] ?? ''));
$cust  = $input['customer'] ?? [];
$cart  = $input['cart'] ?? [];

$product = new Product(db());
$bill    = new Bill($product->priceMap());
$bill->load(is_array($cart) ? $cart : []);
$s = $bill->summary();

$peso = fn ($n) => 'PHP ' . number_format((float) $n, 2);

$lines = [];
$lines[] = '=====================================';
$lines[] = '        ONLINE BILLING SYSTEM';
$lines[] = '=====================================';
$lines[] = 'Customer : ' . ($cust['name'] ?? 'Walk-in Customer');
$lines[] = 'Contact  : ' . ($cust['contact'] ?? '-');
$lines[] = 'Order No : ' . ($cust['order_number'] ?? '-');
$lines[] = 'Date     : ' . date('Y-m-d H:i');
$lines[] = '-------------------------------------';
$lines[] = 'CATEGORY TOTALS';
foreach ($s['category_totals'] as $cat => $total) {
    $lines[] = sprintf('  %-26s %s', $cat, $peso($total));
}
$lines[] = '-------------------------------------';
$lines[] = 'TAXES';
foreach ($s['category_taxes'] as $cat => $tax) {
    $rate = ($s['tax_rates'][$cat] ?? 0) * 100;
    $lines[] = sprintf('  %-20s (%4.1f%%) %s', $cat, $rate, $peso($tax));
}
$lines[] = '-------------------------------------';
$lines[] = sprintf('  %-26s %s', 'Subtotal',   $peso($s['subtotal']));
$lines[] = sprintf('  %-26s %s', 'Total Tax',  $peso($s['total_tax']));
$lines[] = sprintf('  %-26s %s', 'GRAND TOTAL',$peso($s['grand_total']));
$lines[] = '=====================================';
$lines[] = 'Thank you for your purchase!';

$body = implode("\n", $lines);

// Attempt an actual send only if a recipient was given.
$sent = false;
if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
    $headers = 'From: billing@onlinebilling.local' . "\r\n";
    // mail() will silently fail on a stock XAMPP without an SMTP relay — that
    // is fine; we still return the body for on-screen display.
    $sent = @mail($to, 'Your Online Billing Receipt', $body, $headers);
}

json_response([
    'ok'   => true,
    'sent' => $sent,
    'to'   => $to,
    'body' => $body,
]);
