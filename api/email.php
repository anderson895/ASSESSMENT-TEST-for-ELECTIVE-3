<?php
/**
 * api/email.php
 * -----------------------------------------------------------------
 * Ginagamit ito ng E-MAIL at PRINT buttons.
 * Ginagawa nito ang teksto ng resibo. Kung may e-mail address,
 * susubukan itong ipadala; kung wala, ipapakita lang sa screen.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/bootstrap.php';

/**
 * Gawing pera ang numero. Halimbawa: 149.5 => "PHP 149.50"
 */
function pesoText($amount)
{
    return 'PHP ' . number_format($amount, 2);
}

// ---- Kunin ang datos mula sa webpage ----
$input = json_input();

$to = '';
if (isset($input['to'])) {
    $to = trim($input['to']);
}

$customer = array();
if (isset($input['customer'])) {
    $customer = $input['customer'];
}

$cart = array();
if (isset($input['cart'])) {
    $cart = $input['cart'];
}

// ---- Kalkulahin ang bill ----
$productModel = new Product(db());
$bill         = new Bill($productModel->priceMap());
$bill->load($cart);
$summary = $bill->summary();

// ---- Kunin ang pangalan, contact, at order number ----
$name    = 'Walk-in Customer';
$contact = '-';
$orderNo = '-';

if (isset($customer['name']) && $customer['name'] != '')                 { $name    = $customer['name']; }
if (isset($customer['contact']) && $customer['contact'] != '')           { $contact = $customer['contact']; }
if (isset($customer['order_number']) && $customer['order_number'] != '') { $orderNo = $customer['order_number']; }

// ---- Buuin ang resibo, linya bawat linya ----
$lines = array();

$lines[] = '=====================================';
$lines[] = '        ONLINE BILLING SYSTEM';
$lines[] = '      Mika . Ricky . Angeline';
$lines[] = '=====================================';
$lines[] = 'Customer : ' . $name;
$lines[] = 'Contact  : ' . $contact;
$lines[] = 'Order No : ' . $orderNo;
$lines[] = 'Date     : ' . date('Y-m-d H:i');
$lines[] = '-------------------------------------';

// Total ng bawat kategorya
$lines[] = 'CATEGORY TOTALS';
foreach ($summary['category_totals'] as $category => $total) {
    // Ang %-26s ay para pantay-pantay ang pagkakahanay ng teksto.
    $lines[] = sprintf('  %-26s %s', $category, pesoText($total));
}
$lines[] = '-------------------------------------';

// Buwis ng bawat kategorya
$lines[] = 'TAXES';
foreach ($summary['category_taxes'] as $category => $tax) {
    $rate    = $summary['tax_rates'][$category] * 100;   // gawing porsyento
    $lines[] = sprintf('  %-20s (%4.1f%%) %s', $category, $rate, pesoText($tax));
}
$lines[] = '-------------------------------------';

// Kabuuang komputasyon
$lines[] = sprintf('  %-26s %s', 'Subtotal',    pesoText($summary['subtotal']));
$lines[] = sprintf('  %-26s %s', 'Total Tax',   pesoText($summary['total_tax']));
$lines[] = sprintf('  %-26s %s', 'GRAND TOTAL', pesoText($summary['grand_total']));
$lines[] = '=====================================';
$lines[] = 'Thank you for your purchase!';

// Pagsama-samahin ang lahat ng linya.
$body = implode("\n", $lines);

// ---- Subukang ipadala kung may e-mail address ----
$sent = false;

if ($to != '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {
    $headers = 'From: billing@onlinebilling.local';

    // Ang mail() ay hindi gumagana sa karaniwang XAMPP dahil walang
    // mail server. Kapag nabigo, ipapakita na lang ang resibo sa screen.
    $sent = @mail($to, 'Your Online Billing Receipt', $body, $headers);
}

// ---- Ibalik ang resibo sa webpage ----
json_response(array(
    'ok'   => true,
    'sent' => $sent,
    'to'   => $to,
    'body' => $body
));
