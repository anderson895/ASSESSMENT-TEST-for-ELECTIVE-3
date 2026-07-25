<?php
/**
 * api/calculate.php
 * -----------------------------------------------------------------
 * Ginagamit ito ng TOTAL button.
 * Kinakalkula ang total ng bawat kategorya, buwis, subtotal,
 * total tax, at grand total. Hindi pa ito nagsi-save sa database.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/bootstrap.php';

// Kunin ang mga binili mula sa webpage.
$input = json_input();
$cart  = array();
if (isset($input['cart'])) {
    $cart = $input['cart'];
}

if (!is_array($cart)) {
    json_response(array('ok' => false, 'error' => 'Invalid cart.'), 422);
}

// 1) Kunin ang presyo ng lahat ng produkto.
$productModel = new Product(db());
$priceList    = $productModel->priceMap();

// 2) Ipasa sa Bill class para kalkulahin.
$bill = new Bill($priceList);
$bill->load($cart);

// 3) Ibalik ang resulta sa webpage.
json_response(array(
    'ok'      => true,
    'summary' => $bill->summary()
));
