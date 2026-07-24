<?php
/**
 * api/find_customer.php
 * POST { "keyword": "<contact or order number>" }
 * -> Looks up a customer by Contact Number OR Order Number (Find button).
 */
require_once __DIR__ . '/../config/bootstrap.php';

$input   = json_input();
$keyword = trim((string) ($input['keyword'] ?? ''));

if ($keyword === '') {
    json_response(['ok' => false, 'error' => 'Please enter a Contact Number or Order Number.'], 422);
}

$customer = (new Customer(db()))->find($keyword);

if (!$customer) {
    json_response(['ok' => false, 'error' => 'No customer found for "' . $keyword . '".'], 404);
}

json_response(['ok' => true, 'customer' => $customer]);
