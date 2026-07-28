<?php
/**
 * check.php — SETUP CHECKER
 * -----------------------------------------------------------------
 * Buksan ito sa BAGONG PC pagkatapos i-copy ang project:
 *     http://localhost/OnlineBillingSystem/check.php
 *
 * Sasabihin nito kung ano ang KULANG — PHP, database, files, o folder
 * permission — para hindi na manghula kung bakit hindi gumagana.
 *
 * SADYANG walang require sa ibang file ang itaas na bahagi nito, at
 * sarili nitong CSS ang gamit. Kaya gumagana pa rin ito kahit sira o
 * kulang ang install.
 * -----------------------------------------------------------------
 */

$root    = __DIR__;
$results = array();   // bawat item: [status, label, detail]
$fail    = 0;
$warn    = 0;

/** Magdagdag ng resulta sa listahan. */
function check($status, $label, $detail)
{
    global $results, $fail, $warn;

    $results[] = array($status, $label, $detail);

    if ($status == 'fail') { $fail++; }
    if ($status == 'warn') { $warn++; }
}

/* =====================================================================
   1) PHP
   ===================================================================== */
$phpOk = version_compare(PHP_VERSION, '7.0.0', '>=');
check($phpOk ? 'pass' : 'fail', 'PHP version',
    PHP_VERSION . ($phpOk ? '' : ' — too old. Use XAMPP with PHP 7.0 or newer.'));

$needed = array(
    'pdo'       => 'Kailangan para makakonekta sa database.',
    'pdo_mysql' => 'Kailangan para sa MySQL. Buksan sa php.ini: extension=pdo_mysql',
    'gd'        => 'Kailangan para suriin ang ina-upload na larawan sa Products page.',
    'mbstring'  => 'Kailangan para sa teksto sa resibo.'
);

foreach ($needed as $extension => $why) {
    $loaded = extension_loaded($extension);
    check($loaded ? 'pass' : 'fail', 'PHP extension: ' . $extension,
        $loaded ? 'loaded' : 'MISSING — ' . $why);
}

/* =====================================================================
   2) MGA FILE NA DAPAT NANDIYAN
   ---------------------------------------------------------------------
   Ito ang madalas na dahilan kung bakit "gumagana sa akin pero hindi
   sa kanila" — hindi nakopya ang buong folder.
   ===================================================================== */
$mustExist = array(
    'index.php',
    'products.php',
    'config/bootstrap.php',
    'config/Database.php',
    'config/store.php',
    'config/mail.php',
    'classes/Product.php',
    'classes/Customer.php',
    'classes/Employee.php',
    'classes/Bill.php',
    'classes/Order.php',
    'classes/Receipt.php',
    'api/calculate.php',
    'api/save_order.php',
    'api/find_customer.php',
    'api/add_customer.php',
    'api/next_order_number.php',
    'api/email.php',
    'api/save_product.php',
    'api/delete_product.php',
    'assets/css/style.css',
    'assets/js/app.js',
    'assets/js/products.js'
);

$missing = array();

foreach ($mustExist as $relative) {
    if (!file_exists($root . '/' . $relative)) {
        $missing[] = $relative;
    }
}

if (count($missing) == 0) {
    check('pass', 'Program files', count($mustExist) . ' files present.');
} else {
    check('fail', 'Program files',
        'MISSING ' . count($missing) . ': ' . implode(', ', $missing) .
        ' — copy the WHOLE project folder again, not just some files.');
}

// ---- Ang JavaScript ang unang namamatay kapag kulang ang copy ----
$appJs = $root . '/assets/js/app.js';

if (file_exists($appJs)) {
    $size = filesize($appJs);
    check($size > 5000 ? 'pass' : 'fail', 'assets/js/app.js',
        number_format($size) . ' bytes' .
        ($size > 5000 ? '' : ' — too small, the file looks truncated. Copy it again.'));
} else {
    check('fail', 'assets/js/app.js',
        'MISSING — this is why the buttons, quantity boxes, and Cashier details do nothing.');
}

// ---- Mga larawan ng produkto ----
$imageFolder = $root . '/assets/img/products';

if (is_dir($imageFolder)) {
    $found = glob($imageFolder . '/*.{svg,png,jpg,jpeg,gif,webp}', GLOB_BRACE);
    $count = is_array($found) ? count($found) : 0;

    check($count > 0 ? 'pass' : 'warn', 'Product images',
        $count . ' image file(s) in assets/img/products' .
        ($count > 0 ? '' : ' — the folder is empty, so every picture will fail to load.'));
} else {
    check('fail', 'Product images', 'The folder assets/img/products is MISSING.');
}

// ---- Puwede bang mag-upload? ----
if (is_dir($imageFolder)) {
    check(is_writable($imageFolder) ? 'pass' : 'warn', 'Image folder writable',
        is_writable($imageFolder)
            ? 'yes — uploading pictures will work.'
            : 'NOT writable — adding a product picture will fail.');
}

// ---- PHPMailer (para sa E-Mail button) ----
$hasMailer = file_exists($root . '/vendor/autoload.php');
check($hasMailer ? 'pass' : 'warn', 'PHPMailer (vendor folder)',
    $hasMailer ? 'present' : 'missing — only the E-Mail button will fail. Copy the vendor folder.');

// ---- Tunay na SMTP password (hindi kasama sa git) ----
$hasMailLocal = file_exists($root . '/config/mail.local.php');
check($hasMailLocal ? 'pass' : 'warn', 'config/mail.local.php',
    $hasMailLocal
        ? 'present'
        : 'missing — this file is deliberately not shared. Copy config/mail.local.example.php ' .
          'to config/mail.local.php and put the Gmail app password in it. Only e-mail needs this.');

/* =====================================================================
   3) DATABASE
   ===================================================================== */
$dbReady = false;

if (extension_loaded('pdo_mysql')) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=online_billing;charset=utf8mb4', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        check('pass', 'Database connection', 'Connected to "online_billing" as root.');
        $dbReady = true;

        // Tingnan ang bawat table.
        $tables = array('categories', 'products', 'employees', 'customers', 'orders', 'order_items');

        foreach ($tables as $table) {
            try {
                $rows = $pdo->query("SELECT COUNT(*) FROM `" . $table . "`")->fetchColumn();

                // Dapat may laman ang tatlong ito para gumana ang billing.
                $needsRows = in_array($table, array('categories', 'products', 'employees'));
                $status    = ($needsRows && $rows == 0) ? 'warn' : 'pass';

                check($status, 'Table: ' . $table,
                    $rows . ' row(s)' .
                    ($status == 'warn' ? ' — EMPTY. Re-import database/online_billing.sql.' : ''));
            } catch (Exception $e) {
                check('fail', 'Table: ' . $table,
                    'MISSING — import database/online_billing.sql in phpMyAdmin.');
            }
        }
    } catch (Exception $e) {
        check('fail', 'Database connection',
            $e->getMessage() .
            ' — start MySQL in the XAMPP Control Panel, then create the "online_billing" ' .
            'database in phpMyAdmin and import database/online_billing.sql.');
    }
} else {
    check('fail', 'Database connection', 'Skipped because pdo_mysql is not loaded.');
}

$total = count($results);
$pass  = $total - $fail - $warn;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Check — Online Billing System</title>
    <style>
        /* Sariling CSS — gumagana kahit nawawala ang assets/css/style.css. */
        body { margin:0; padding:26px 16px; background:#FFF7FA; color:#374151;
               font-family:"Segoe UI",Tahoma,Arial,sans-serif; line-height:1.55; }
        .box { max-width:900px; margin:0 auto; background:#fff; border:1px solid #F8BBD0;
               border-radius:12px; padding:24px; box-shadow:0 1px 3px rgba(216,27,96,.07); }
        h1 { margin:0 0 4px; color:#D81B60; font-size:1.4rem; }
        .sub { margin:0 0 20px; color:#8A8F98; font-size:.86rem; }
        .banner { padding:13px 16px; border-radius:8px; font-weight:600; margin-bottom:20px; }
        .banner.good { background:#E8F6EE; color:#1B7A3D; }
        .banner.bad  { background:#FDECEF; color:#C62828; }
        table { width:100%; border-collapse:collapse; font-size:.88rem; }
        th { text-align:left; padding:8px 6px; border-bottom:2px solid #F8BBD0;
             color:#D81B60; font-size:.68rem; letter-spacing:.7px; text-transform:uppercase; }
        td { padding:9px 6px; border-bottom:1px solid #F1E3EA; vertical-align:top; }
        .tag { display:inline-block; padding:2px 10px; border-radius:999px;
               font-size:.7rem; font-weight:700; white-space:nowrap; }
        .pass { background:#E8F6EE; color:#1B7A3D; }
        .warn { background:#FDF3E0; color:#A06B12; }
        .fail { background:#FDECEF; color:#C62828; }
        .label { font-weight:600; white-space:nowrap; }
        .detail { color:#6B7280; }
        .steps { margin:22px 0 0; padding:16px 18px; background:#FFF3F8;
                 border-radius:8px; font-size:.86rem; }
        .steps h2 { margin:0 0 8px; font-size:.95rem; color:#D81B60; }
        .steps ol { margin:0; padding-left:20px; }
        .steps li { margin-bottom:5px; }
        code { background:#fff; border:1px solid #F1E3EA; border-radius:4px;
               padding:1px 5px; font-size:.85em; }
        a.back { display:inline-block; margin-top:18px; color:#C2185B; font-weight:600; }
    </style>
</head>
<body>
<div class="box">
    <h1>Setup Check</h1>
    <p class="sub">Online Billing System — run this on a new PC to see what is missing.</p>

    <?php if ($fail == 0 && $warn == 0): ?>
        <div class="banner good">Everything passed — the system should run normally.</div>
    <?php elseif ($fail == 0): ?>
        <div class="banner good">
            No blocking problems (<?= $pass ?> passed, <?= $warn ?> warning<?= $warn == 1 ? '' : 's' ?>).
            Billing will work — check the warnings below for optional features.
        </div>
    <?php else: ?>
        <div class="banner bad">
            <?= $fail ?> problem<?= $fail == 1 ? '' : 's' ?> found — fix the red rows below.
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr><th>Status</th><th>Check</th><th>Details</th></tr>
        </thead>
        <tbody>
        <?php foreach ($results as $row): ?>
            <tr>
                <td><span class="tag <?= $row[0] ?>"><?= strtoupper($row[0]) ?></span></td>
                <td class="label"><?= htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="detail"><?= htmlspecialchars($row[2], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="steps">
        <h2>Setting this up on another PC</h2>
        <ol>
            <li>Install <strong>XAMPP</strong> and start <strong>Apache</strong> and <strong>MySQL</strong>.</li>
            <li>Copy the <strong>WHOLE project folder</strong> into <code>C:\xampp\htdocs\</code>.
                Copying only some files is the usual cause of dead buttons — the page still looks
                right because the CSS loaded, but <code>assets/js/app.js</code> never arrived.</li>
            <li>In <strong>phpMyAdmin</strong>, create a database named <code>online_billing</code>,
                then import <code>database/online_billing.sql</code>.</li>
            <li>For the E-Mail button only: copy <code>config/mail.local.example.php</code> to
                <code>config/mail.local.php</code> and fill in the Gmail app password.
                That file is intentionally not shared, so it never travels with the project.</li>
            <li>Open this page again — every row should be green.</li>
        </ol>
    </div>

    <a class="back" href="index.php">&larr; Back to Billing</a>
</div>
</body>
</html>
