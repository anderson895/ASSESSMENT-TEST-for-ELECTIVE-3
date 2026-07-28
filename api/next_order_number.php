<?php
/**
 * api/next_order_number.php
 * -----------------------------------------------------------------
 * Ibinabalik nito ang SUSUNOD na order number, hal. ORD-20260728-0001.
 *
 * Tinatawag ito ng webpage pagkabukas pa lang, para may makita agad
 * na order number ang cashier — AUTOMATIC na, hindi na tinatype.
 *
 * PREVIEW pa lang ito. Ang PANGWAKAS na order number ay ginagawa ng
 * api/save_order.php kapag pinindot na ang BILL button.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/bootstrap.php';

$orderModel = new Order(db());

json_response(array(
    'ok'           => true,
    'order_number' => $orderModel->nextOrderNumber()
));
