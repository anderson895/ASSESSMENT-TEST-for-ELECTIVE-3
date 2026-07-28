<?php
/**
 * api/delete_product.php
 * -----------------------------------------------------------------
 * Binubura ang isang produkto mula sa products table.
 *
 * MAHALAGA: hindi puwedeng burahin ang produktong NAIBENTA NA.
 * Ang order_items ay may FOREIGN KEY papunta sa products, at ang
 * Order History ay JOIN sa products — kapag binura, mawawala ang
 * item na iyon sa mga lumang resibo. Kaya tinatanggihan natin ito
 * at sinasabi sa cashier na gawing 0 ang stock para maitago.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/bootstrap.php';

$input     = json_input();
$productId = isset($input['product_id']) ? (int) $input['product_id'] : 0;

if ($productId <= 0) {
    json_response(array('ok' => false, 'error' => 'No product was selected.'), 422);
}

try {
    $pdo   = db();
    $model = new Product($pdo);

    $product = $model->find($productId);

    if ($product == null) {
        json_response(array('ok' => false, 'error' => 'That product no longer exists.'), 404);
    }

    // May kasaysayan na ba ito sa mga order?
    $sold = $model->timesSold($productId);

    if ($sold > 0) {
        json_response(array(
            'ok'    => false,
            'error' => '"' . $product['product_name'] . '" was already sold in ' . $sold .
                       ' past order' . ($sold == 1 ? '' : 's') . ', so deleting it would erase it ' .
                       'from those receipts. Set its Stock to 0 instead to stop selling it.'
        ), 409);
    }

    $model->delete($productId);

    json_response(array(
        'ok'      => true,
        'message' => '"' . $product['product_name'] . '" was deleted.'
    ));

} catch (Exception $e) {
    json_response(array('ok' => false, 'error' => 'Database error: ' . $e->getMessage()), 500);
}
