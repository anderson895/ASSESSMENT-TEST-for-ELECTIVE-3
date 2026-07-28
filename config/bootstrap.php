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
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Bill.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/Receipt.php';

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
 * Kunin ang impormasyon ng tindahan (config/store.php).
 * Isang beses lang ito binabasa kahit ilang beses tawagin.
 */
function store()
{
    static $store = null;

    if ($store == null) {
        $store = require __DIR__ . '/store.php';
    }
    return $store;
}

/**
 * Kunin ang koneksyon sa database.
 * Isang koneksyon lang ang ginagawa sa buong request — mahalaga ito
 * para gumana nang tama ang transaction sa pag-save ng order.
 */
function db()
{
    static $connection = null;

    if ($connection == null) {
        $database   = new Database();
        $connection = $database->connect();
    }

    return $connection;
}

/**
 * Buuin ang Bill mula sa ipinadala ng webpage.
 *
 * Ginagamit ito ng TOTAL, BILL, E-MAIL, at PRINT — kaya siguradong
 * PAREHO ang komputasyon sa apat na iyon.
 *
 * Ang presyo, kategorya, at stock ay galing sa DATABASE (hindi sa
 * webpage), kaya hindi ito mapepeke ng gumagamit.
 */
function build_bill($pdo, $input)
{
    $cart = isset($input['cart']) ? $input['cart'] : array();

    $productModel = new Product($pdo);

    $bill = new Bill($productModel->mapById());
    $bill->load($cart);

    $bill->setDiscount(
        isset($input['discount_type']) ? $input['discount_type'] : 'None'
    );

    $bill->setPayment(
        isset($input['payment_method'])  ? $input['payment_method']  : 'Cash',
        isset($input['amount_received']) ? $input['amount_received'] : 0
    );

    return $bill;
}

/**
 * Kunin ang napiling cashier mula sa ipinadala ng webpage.
 * Ibabalik ang null kapag walang napili.
 */
function input_cashier($pdo, $input)
{
    $employeeId = isset($input['employee_id']) ? (int) $input['employee_id'] : 0;

    $employeeModel = new Employee($pdo);
    return $employeeModel->find($employeeId);
}

/**
 * Kunin ang pangalan at contact number ng customer,
 * malinis na at handa nang gamitin.
 */
function input_customer($input)
{
    $customer = array();
    if (isset($input['customer']) && is_array($input['customer'])) {
        $customer = $input['customer'];
    }

    return array(
        'name'    => isset($customer['name'])    ? trim($customer['name'])    : '',
        'contact' => isset($customer['contact']) ? trim($customer['contact']) : ''
    );
}

/**
 * Panghuling saklolo: kapag may error na walang nakasalo, JSON pa rin
 * ang ibabalik sa webpage — hindi blangkong pahina.
 *
 * Ang index.php ay may sarili nang `try / catch`, kaya hindi ito
 * umaabot dito — malinis na alert ang ipinapakita nito sa halip.
 */
set_exception_handler(function ($e) {
    json_response(array('ok' => false, 'error' => $e->getMessage()), 500);
});
