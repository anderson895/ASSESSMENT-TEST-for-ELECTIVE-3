<?php
/**
 * bootstrap.php
 * Common includes + tiny JSON helpers used by every API endpoint.
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0'); // errors are returned as JSON, not printed

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Bill.php';
require_once __DIR__ . '/../classes/Order.php';

/** Send a JSON response and stop. */
function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Read and decode a JSON request body. */
function json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}

/** Get a shared PDO connection or fail with JSON. */
function db(): PDO
{
    try {
        return (new Database())->connect();
    } catch (Throwable $e) {
        json_response(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}
