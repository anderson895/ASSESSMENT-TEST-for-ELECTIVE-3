<?php
/**
 * config/store.php
 * -----------------------------------------------------------------
 * Impormasyon ng tindahan. Ito ang lumalabas sa TAAS ng resibo
 * (Bill / E-Mail / Print) at sa header ng webpage.
 *
 * >>> PALITAN ANG ADDRESS, CONTACT, AT EMAIL NG TOTOO NINYONG DETALYE. <<<
 * Dito lang kayo mag-e-edit — kusa nang susunod ang buong sistema.
 * -----------------------------------------------------------------
 */
return array(

    // Pangalan ng tindahan.
    'name'    => 'MRA STORE',

    // Ang maliit na teksto sa ilalim ng pangalan.
    'tagline' => 'MICA • RICKY • ANGELINE',

    // Address ng tindahan (lumalabas sa resibo).
    'address' => '123 Rizal Avenue, Barangay San Roque, Quezon City, Metro Manila',

    // Contact number ng tindahan.
    'contact' => '(02) 8123-4567  /  0917 123 4567',

    // E-mail ng tindahan.
    'email'   => 'mrastore.billing@gmail.com',

    // Logo. Ito ang path mula sa root ng project (para sa webpage),
    // at ito rin ang ini-embed sa e-mail na resibo.
    'logo'    => 'assets/img/logo.png',
);
