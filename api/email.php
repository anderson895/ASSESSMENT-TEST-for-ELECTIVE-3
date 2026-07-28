<?php
/**
 * api/email.php
 * -----------------------------------------------------------------
 * Ginagamit ito ng E-MAIL at PRINT buttons.
 *
 * Ginagawa nito ang RESIBO (may logo, detalye ng tindahan, cashier,
 * at larawan ng bawat produkto). Kung may e-mail address, susubukan
 * itong ipadala; kung wala, ipapakita lang sa screen.
 *
 * Iisang Receipt class lang ang ginagamit dito at sa BILL button,
 * kaya laging PAREHO ang hitsura ng makikita, ng maipa-print,
 * at ng maipapadala sa e-mail.
 * -----------------------------------------------------------------
 */
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// ---- Kunin ang datos mula sa webpage ----
$input    = json_input();
$customer = input_customer($input);

$to = isset($input['to']) ? trim($input['to']) : '';

$pdo = db();

// ---- Kalkulahin ang bill at kunin ang cashier ----
$bill    = build_bill($pdo, $input);
$cashier = input_cashier($pdo, $input);

// ---- Anong order number ang ilalagay sa resibo? ----
// Kung na-bill na, ipinapasa ng webpage ang totoong order number.
// Kung preview pa lang, ang susunod na order number ang ipapakita.
$orderNumber = isset($input['order_number']) ? trim($input['order_number']) : '';

if ($orderNumber == '') {
    $orderModel  = new Order($pdo);
    $orderNumber = $orderModel->nextOrderNumber();
}

// ---- Buuin ang resibo ----
$receipt = new Receipt(store(), $bill, $customer, $cashier, $orderNumber);

$html = $receipt->html(false);   // para sa screen at sa print
$text = $receipt->text();        // plain text na bersyon

// ---- Subukang ipadala kung may e-mail address ----
$sent  = false;
$error = '';

if ($to != '' && filter_var($to, FILTER_VALIDATE_EMAIL)) {

    // Bersyong pang-e-mail: naka-embed na ang mga larawan sa loob.
    $emailHtml = $receipt->html(true);
    $images    = $receipt->embeddedImages();

    // I-load ang SMTP settings (Gmail) mula sa config/mail.php.
    $mailConfig = require __DIR__ . '/../config/mail.php';
    $store      = store();

    $mailer = new PHPMailer(true);   // true = magtapon ng exception kapag may error

    try {
        // ---- SMTP setup ----
        $mailer->isSMTP();
        $mailer->Host     = $mailConfig['host'];
        $mailer->SMTPAuth = true;
        $mailer->Username = $mailConfig['username'];
        $mailer->Password = $mailConfig['password'];
        $mailer->Port     = (int) $mailConfig['port'];
        $mailer->CharSet  = 'UTF-8';   // para lumabas nang tama ang ₱

        if ($mailConfig['encryption'] === 'ssl') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        // ---- Sino ang nagpadala at sino ang tatanggap ----
        $mailer->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
        $mailer->addAddress($to);

        // ---- Idikit ang mga larawan (logo at produkto) sa mensahe ----
        // Kaya kahit offline ang customer, kita pa rin ang resibo.
        foreach ($images as $cid => $path) {
            $mailer->addEmbeddedImage($path, $cid, basename($path));
        }

        // ---- Nilalaman ng e-mail ----
        $mailer->Subject = $store['name'] . ' Official Receipt — ' . $orderNumber;
        $mailer->isHTML(true);
        $mailer->Body    = $emailHtml;
        $mailer->AltBody = $text;   // kapag hindi kayang magpakita ng HTML

        $mailer->send();
        $sent = true;

    } catch (PHPMailerException $e) {
        // Ibalik ang tunay na dahilan (hal. maling password, walang internet).
        $error = $mailer->ErrorInfo;
    }
}

// ---- Ibalik ang resibo sa webpage ----
json_response(array(
    'ok'           => true,
    'sent'         => $sent,
    'to'           => $to,
    'order_number' => $orderNumber,
    'receipt_html' => $html,
    'receipt_text' => $text,
    'summary'      => $bill->summary(),
    'error'        => $error
));
