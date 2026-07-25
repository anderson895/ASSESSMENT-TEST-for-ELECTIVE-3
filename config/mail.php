<?php
/**
 * config/mail.php
 * -----------------------------------------------------------------
 * Mga setting para sa pagpapadala ng e-mail gamit ang Gmail SMTP.
 *
 * MAHALAGA: Ang tunay na username at App Password ay HINDI dito
 * inilalagay dahil naka-commit ang file na ito sa Git/GitHub.
 * Sa halip, ilagay ang mga ito sa:
 *
 *     config/mail.local.php     (HINDI naka-commit — nasa .gitignore)
 *
 * Kopyahin ang  config/mail.local.example.php  at palitan ang pangalan
 * nito ng  mail.local.php , tapos ilagay ang tunay na values doon.
 * -----------------------------------------------------------------
 */

// Ligtas na default na walang lihim na credentials.
$config = array(
    'host'       => 'smtp.gmail.com',
    'port'       => 587,               // 587 = STARTTLS
    'encryption' => 'tls',             // 'tls' para sa 587, 'ssl' para sa 465
    'username'   => '',                // ilagay sa mail.local.php
    'password'   => '',                // ilagay sa mail.local.php
    'from_email' => '',                // ilagay sa mail.local.php
    'from_name'  => 'Online Billing System',
);

// I-load ang lokal na credentials kung meron. Ito ang naglalaman ng
// tunay na App Password at HINDI kasama sa Git.
$localFile = __DIR__ . '/mail.local.php';
if (is_file($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        $config = array_merge($config, $local);
    }
}

return $config;
