<?php
/**
 * api/calculate.php
 * POST { "cart": [ {product_id, category, quantity}, ... ] }
 * -> Returns the full bill summary (category totals, taxes, subtotal,
 *    total tax, grand total). Backs both the Total and Bill buttons.
 */
require_once __DIR__ . '/../config/bootstrap.php';

$input = json_input();
$cart  = $input['cart'] ?? [];

if (!is_array($cart)) {
    json_response(['ok' => false, 'error' => 'Invalid cart payload.'], 422);
}

$product = new Product(db());
$bill    = new Bill($product->priceMap());
$bill->load($cart);

json_response(['ok' => true, 'summary' => $bill->summary()]);
