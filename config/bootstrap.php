<?php
/**
 * bootstrap.php
 * -----------------------------------------------------------------
 * Ito ang unang tumatakbo sa bawat API file.
 * Dito ini-load ang lahat ng class at ang mga tulong na function.
 * -----------------------------------------------------------------
 */

// Huwag ipakita ang error sa screen - JSON ang ibabalik natin.
error_reporting(E_ALL);
ini_set('display_errors', '0');

// ---- I-load ang lahat ng class na gagamitin ----
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Bill.php';
require_once __DIR__ . '/../classes/Order.php';

/**
 * Magpadala ng sagot sa webpage bilang JSON, tapos itigil na.
 */
function json_response($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Basahin ang datos na ipinadala ng webpage (JSON format).
 */
function json_input()
{
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        return array();
    }
    return $data;
}

/**
 * Kunin ang koneksyon sa database.
 */
function db()
{
    try {
        $database = new Database();
        return $database->connect();
    } catch (Exception $e) {
        json_response(array('ok' => false, 'error' => $e->getMessage()), 500);
    }
}
