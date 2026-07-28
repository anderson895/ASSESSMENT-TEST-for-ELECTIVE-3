<?php
/**
 * Receipt.php
 * -----------------------------------------------------------------
 * Ito ang gumagawa ng RESIBO. Iisa lang ang class na ito para sa
 * tatlong gamit — kaya laging PAREHO ang hitsura ng:
 *   1. Bill / Print preview sa webpage
 *   2. E-mail na ipinapadala sa customer
 *   3. Naka-print na papel
 *
 * NASA TAAS NG RESIBO:
 *   Store Logo, Store Name, Store Address, Contact Number, E-mail,
 *   Cashier Name, Employee ID, Position, Shift.
 *
 * MAY LARAWAN ang bawat produkto sa listahan.
 *
 * TUNGKOL SA LARAWAN SA E-MAIL:
 *   Hindi basta-basta lumalabas ang larawan sa e-mail kung link lang
 *   ang ilalagay (nasa localhost kasi ang mga file). Kaya kapag
 *   e-mail ang gamit, ini-embed natin ang larawan sa loob mismo ng
 *   mensahe gamit ang "cid:" — kaya kahit offline, makikita pa rin.
 * -----------------------------------------------------------------
 */
class Receipt
{
    /** Mga kulay na ginagamit sa resibo. */
    const COLOR_HEADER = '#D81B60';
    const COLOR_ACCENT = '#C2185B';
    const COLOR_LIGHT  = '#F8BBD0';
    const COLOR_TEXT   = '#374151';

    private $store;         // galing sa config/store.php
    private $bill;          // Bill object (kompleto na ang komputasyon)
    private $customer;      // ['name' => ..., 'contact' => ...]
    private $cashier;       // row mula sa employees table (pwedeng null)
    private $orderNumber;   // hal. ORD-20260728-0001
    private $dateTime;      // kailan ginawa ang resibo

    /** Mga larawang kailangang i-embed sa e-mail: [ cid => buong path ] */
    private $embedded = array();

    /** true kapag e-mail ang gamit (gagamit ng "cid:" sa halip na link). */
    private $forEmail = false;

    public function __construct($store, $bill, $customer, $cashier, $orderNumber, $dateTime = null)
    {
        $this->store       = $store;
        $this->bill        = $bill;
        $this->customer    = $customer;
        $this->cashier     = $cashier;
        $this->orderNumber = $orderNumber;
        $this->dateTime    = $dateTime == null ? date('Y-m-d H:i:s') : $dateTime;
    }

    /* =================================================================
       MGA TULONG NA FUNCTION
       ================================================================= */

    /** Gawing ligtas ang teksto bago ilagay sa HTML. */
    private function e($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }

    /** Gawing pera ang numero. Halimbawa: 149.5 => "₱149.50" */
    private function peso($amount)
    {
        return '₱' . number_format((float) $amount, 2);
    }

    /** Buong path ng file sa computer (hindi link). */
    private function filePath($relativePath)
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR
             . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    /**
     * Ano ang ilalagay sa src="" ng larawan?
     *   - Sa webpage : ang normal na path (hal. assets/img/logo.png)
     *   - Sa e-mail  : "cid:xxxx" — naka-embed na ang larawan sa mensahe
     *
     * Kapag wala ang file, ibabalik ang blangko para hindi masira
     * ang hitsura ng resibo.
     */
    private function imageSrc($relativePath)
    {
        if ($relativePath == '' || $relativePath == null) {
            return '';
        }

        $fullPath = $this->filePath($relativePath);

        if (!is_file($fullPath)) {
            return '';
        }

        // ---- Para sa WEBPAGE at PRINT: link lang, kahit SVG. ----
        if (!$this->forEmail) {
            return $this->e($relativePath);
        }

        // ---- Para sa E-MAIL ----
        // Hindi ipinapakita ng Gmail at Outlook ang SVG sa loob ng e-mail.
        // Kaya kapag SVG ang larawan, hahanap muna tayo ng katumbas na
        // .png / .jpg. Kapag wala, wala na lang larawan sa e-mail —
        // buo pa rin ang resibo, may pangalan at presyo pa rin.
        $emailPath = $this->emailSafeImage($fullPath);

        if ($emailPath == '') {
            return '';
        }

        // Gumawa ng maikli at natatanging pangalan para sa larawan.
        $cid = 'img' . substr(md5($emailPath), 0, 12);
        $this->embedded[$cid] = $emailPath;

        return 'cid:' . $cid;
    }

    /**
     * Maghanap ng larawang kayang ipakita ng e-mail.
     *
     * Kapag ang nakatakda ay `rice.svg`, titingnan muna nito kung may
     * `rice.png`, `rice.jpg`, o `rice.jpeg` sa parehong folder.
     *
     * KAYA: kapag pinalitan ninyo ang mga placeholder ng TOTOONG litrato
     * (.jpg o .png), kusa nang lalabas ang larawan sa e-mail — walang
     * kailangang baguhin sa code.
     */
    private function emailSafeImage($fullPath)
    {
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        // Ang PNG at JPG ay ligtas na sa lahat ng e-mail app.
        if ($extension != 'svg') {
            return $fullPath;
        }

        $withoutExtension = substr($fullPath, 0, -strlen('svg'));

        foreach (array('png', 'jpg', 'jpeg') as $candidate) {
            if (is_file($withoutExtension . $candidate)) {
                return $withoutExtension . $candidate;
            }
        }

        return '';   // SVG lang ang meron - laktawan sa e-mail
    }

    /**
     * Mga larawang kailangang i-embed sa e-mail.
     * Tawagin ito PAGKATAPOS ng html(true).
     */
    public function embeddedImages()
    {
        return $this->embedded;
    }

    /**
     * Isang linya ng napiling opsyon, hal.  [X] Cash   [ ] GCash
     * Ito ang nagpapakita kung alin ang napili sa Payment Method
     * at sa Discount Type.
     */
    private function optionRow($label, $isChosen)
    {
        $mark   = $isChosen ? '&#10003;' : '&nbsp;';
        $weight = $isChosen ? '700' : '400';
        $color  = $isChosen ? self::COLOR_ACCENT : '#9CA3AF';

        return '<span style="display:inline-block;margin-right:16px;font-weight:' . $weight . ';color:' . $color . ';">'
             . '<span style="display:inline-block;width:15px;height:15px;line-height:15px;text-align:center;'
             . 'border:1.5px solid ' . $color . ';border-radius:3px;margin-right:5px;font-size:11px;">'
             . $mark . '</span>'
             . $this->e($label)
             . '</span>';
    }

    /** Isang linya ng detalye: "Label : Halaga" */
    private function infoRow($label, $value)
    {
        return '<tr>'
             . '<td style="padding:1px 0;font-size:12px;color:#6B7280;white-space:nowrap;">' . $this->e($label) . '</td>'
             . '<td style="padding:1px 0 1px 10px;font-size:12px;color:' . self::COLOR_TEXT . ';font-weight:600;">' . $this->e($value) . '</td>'
             . '</tr>';
    }

    /** Isang linya ng halaga sa buod (hal. Subtotal ... ₱500.00) */
    private function totalRow($label, $value, $isBold = false, $color = null)
    {
        if ($color == null) {
            $color = self::COLOR_TEXT;
        }
        $weight = $isBold ? '700' : '500';

        return '<tr>'
             . '<td style="padding:4px 0;font-size:13px;color:' . $color . ';font-weight:' . $weight . ';">' . $this->e($label) . '</td>'
             . '<td style="padding:4px 0;font-size:13px;color:' . $color . ';font-weight:' . $weight . ';text-align:right;white-space:nowrap;">' . $value . '</td>'
             . '</tr>';
    }

    /** Guhit na pambukod. */
    private function divider()
    {
        return '<div style="border-top:1px dashed ' . self::COLOR_LIGHT . ';margin:12px 0;"></div>';
    }

    /* =================================================================
       ANG HTML NA RESIBO
       ================================================================= */

    /**
     * Buuin ang resibo bilang HTML.
     * $forEmail = true kapag ipapadala sa e-mail (i-e-embed ang larawan).
     */
    public function html($forEmail = false)
    {
        $this->forEmail = $forEmail;
        $this->embedded = array();

        $summary = $this->bill->summary();

        $html  = '<div style="max-width:620px;margin:0 auto;background:#FFFFFF;border:1px solid ' . self::COLOR_LIGHT . ';'
               . 'border-radius:10px;overflow:hidden;font-family:Segoe UI,Helvetica,Arial,sans-serif;color:' . self::COLOR_TEXT . ';">';

        $html .= $this->headerBlock();

        $html .= '<div style="padding:18px 22px 22px;">';
        $html .= $this->transactionBlock();
        $html .= $this->divider();
        $html .= $this->itemsBlock();
        $html .= $this->divider();
        $html .= $this->totalsBlock($summary);
        $html .= $this->divider();
        $html .= $this->paymentBlock($summary);
        $html .= $this->footerBlock();
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * TAAS NG RESIBO:
     * Logo, pangalan, tagline, address, contact number, at e-mail.
     */
    private function headerBlock()
    {
        $logo = $this->imageSrc($this->store['logo']);

        $html = '<div style="background:' . self::COLOR_HEADER . ';color:#FFFFFF;text-align:center;padding:18px 20px 16px;">';

        if ($logo != '') {
            $html .= '<img src="' . $logo . '" width="66" height="66" alt="' . $this->e($this->store['name']) . '"'
                   . ' style="width:66px;height:66px;border-radius:50%;background:#FFFFFF;padding:4px;display:block;margin:0 auto 8px;">';
        }

        $html .= '<div style="font-size:23px;font-weight:700;letter-spacing:3px;">' . $this->e($this->store['name']) . '</div>';
        $html .= '<div style="font-size:11px;letter-spacing:2px;opacity:.92;margin-top:2px;">' . $this->e($this->store['tagline']) . '</div>';
        $html .= '<div style="height:1px;background:rgba(255,255,255,.35);margin:11px auto;max-width:230px;"></div>';
        $html .= '<div style="font-size:11.5px;line-height:1.7;opacity:.95;">'
               . $this->e($this->store['address']) . '<br>'
               . 'Tel: ' . $this->e($this->store['contact']) . '<br>'
               . 'E-mail: ' . $this->e($this->store['email'])
               . '</div>';

        $html .= '</div>';

        // Ang "OFFICIAL RECEIPT" na banner.
        $html .= '<div style="background:#FFF3F8;text-align:center;padding:7px;font-size:11.5px;'
               . 'letter-spacing:3px;font-weight:700;color:' . self::COLOR_ACCENT . ';">OFFICIAL RECEIPT</div>';

        return $html;
    }

    /**
     * Detalye ng transaksyon: Order No., petsa, cashier, at customer.
     */
    private function transactionBlock()
    {
        // ---- Kaliwa: order at customer ----
        $left  = '<table style="border-collapse:collapse;">';
        $left .= $this->infoRow('Order No.', $this->orderNumber);
        $left .= $this->infoRow('Date', date('M d, Y  h:i A', strtotime($this->dateTime)));

        $name    = isset($this->customer['name'])    && $this->customer['name']    != '' ? $this->customer['name']    : 'Walk-in Customer';
        $contact = isset($this->customer['contact']) && $this->customer['contact'] != '' ? $this->customer['contact'] : '-';

        $left .= $this->infoRow('Customer', $name);
        $left .= $this->infoRow('Contact', $contact);
        $left .= '</table>';

        // ---- Kanan: ang cashier na nag-asikaso ----
        $right  = '<table style="border-collapse:collapse;">';

        if ($this->cashier != null) {
            $right .= $this->infoRow('Cashier', $this->cashier['employee_name']);
            $right .= $this->infoRow('Employee ID', $this->cashier['employee_code']);
            $right .= $this->infoRow('Position', $this->cashier['position']);
            $right .= $this->infoRow('Shift', $this->cashier['shift']);
        } else {
            $right .= $this->infoRow('Cashier', 'Not assigned');
        }

        $right .= '</table>';

        return '<table width="100%" style="border-collapse:collapse;">'
             . '<tr>'
             . '<td valign="top" style="padding:0 10px 0 0;">' . $left . '</td>'
             . '<td valign="top" style="padding:0 0 0 10px;border-left:1px dashed ' . self::COLOR_LIGHT . ';">' . $right . '</td>'
             . '</tr></table>';
    }

    /**
     * Ang listahan ng binili — MAY LARAWAN ang bawat produkto.
     */
    private function itemsBlock()
    {
        $html  = '<table width="100%" style="border-collapse:collapse;">';

        // Ulo ng talahanayan.
        $html .= '<tr>'
               . '<th colspan="2" style="text-align:left;font-size:10.5px;letter-spacing:1px;text-transform:uppercase;'
               . 'color:' . self::COLOR_ACCENT . ';padding:0 0 6px;border-bottom:1px solid ' . self::COLOR_LIGHT . ';">Product</th>'
               . '<th style="text-align:center;font-size:10.5px;letter-spacing:1px;text-transform:uppercase;'
               . 'color:' . self::COLOR_ACCENT . ';padding:0 6px 6px;border-bottom:1px solid ' . self::COLOR_LIGHT . ';">Qty</th>'
               . '<th style="text-align:right;font-size:10.5px;letter-spacing:1px;text-transform:uppercase;'
               . 'color:' . self::COLOR_ACCENT . ';padding:0 6px 6px;border-bottom:1px solid ' . self::COLOR_LIGHT . ';">Price</th>'
               . '<th style="text-align:right;font-size:10.5px;letter-spacing:1px;text-transform:uppercase;'
               . 'color:' . self::COLOR_ACCENT . ';padding:0 0 6px;border-bottom:1px solid ' . self::COLOR_LIGHT . ';">Amount</th>'
               . '</tr>';

        foreach ($this->bill->items() as $item) {
            $picture = $this->imageSrc($item['image']);

            // Kapag may larawan, may sarili itong maliit na kahon.
            // Kapag wala (hal. SVG sa e-mail), sasakupin na lang ng
            // pangalan ang dalawang kahon para walang butas.
            if ($picture != '') {
                $nameCell = '<td style="padding:7px 0;border-bottom:1px solid #F5F5F5;font-size:12.5px;">';
                $html    .= '<tr>'
                          . '<td width="42" style="padding:7px 8px 7px 0;border-bottom:1px solid #F5F5F5;">'
                          . '<img src="' . $picture . '" width="34" height="34" alt=""'
                          . ' style="width:34px;height:34px;border-radius:6px;display:block;border:1px solid ' . self::COLOR_LIGHT . ';">'
                          . '</td>';
            } else {
                $nameCell = '<td colspan="2" style="padding:7px 0;border-bottom:1px solid #F5F5F5;font-size:12.5px;">';
                $html    .= '<tr>';
            }

            $html .= $nameCell
                   . '<span style="font-weight:600;">' . $this->e($item['product_name']) . '</span><br>'
                   . '<span style="font-size:10.5px;color:#9CA3AF;">' . $this->e($item['category']) . '</span>'
                   . '</td>'
                   . '<td style="padding:7px 6px;border-bottom:1px solid #F5F5F5;font-size:12.5px;text-align:center;">' . (int) $item['quantity'] . '</td>'
                   . '<td style="padding:7px 6px;border-bottom:1px solid #F5F5F5;font-size:12.5px;text-align:right;white-space:nowrap;">' . $this->peso($item['price']) . '</td>'
                   . '<td style="padding:7px 0;border-bottom:1px solid #F5F5F5;font-size:12.5px;text-align:right;white-space:nowrap;font-weight:600;">' . $this->peso($item['total_price']) . '</td>'
                   . '</tr>';
        }

        $html .= '</table>';

        // Ilan lahat ang piraso.
        $html .= '<div style="text-align:right;font-size:11px;color:#9CA3AF;margin-top:6px;">'
               . 'Total items: ' . (int) $this->bill->totalQuantity() . '</div>';

        return $html;
    }

    /**
     * Ang buod ng komputasyon:
     * Subtotal, Discount, VATable Sales, VAT 12%, at Grand Total.
     */
    private function totalsBlock($summary)
    {
        // ---- Discount Type: ipinapakita ang tatlong pagpipilian ----
        $chosen = $summary['discount_type'];

        $html  = '<div style="font-size:11px;color:#6B7280;margin-bottom:4px;font-weight:600;">Discount Type:</div>';
        $html .= '<div style="font-size:12px;margin-bottom:10px;">'
               . $this->optionRow('Senior Citizen', $chosen == 'Senior Citizen')
               . $this->optionRow('PWD',            $chosen == 'PWD')
               . $this->optionRow('Promo',          $chosen == 'Promo')
               . '</div>';

        // ---- Ang mga halaga ----
        $discountLabel = 'Discount Amount';
        if ($chosen != 'None') {
            $discountLabel = 'Discount Amount (' . $chosen . ' ' . round($summary['discount_rate'] * 100) . '%)';
        }

        $vatLabel = 'VAT (' . round($summary['vat_rate'] * 100) . '%)';

        $html .= '<table width="100%" style="border-collapse:collapse;">';
        $html .= $this->totalRow('Subtotal',      $this->peso($summary['subtotal']));
        $html .= $this->totalRow($discountLabel,  '- ' . $this->peso($summary['discount_amount']), false, self::COLOR_ACCENT);
        $html .= $this->totalRow('VATable Sales', $this->peso($summary['vatable_sales']));
        $html .= $this->totalRow($vatLabel,       $this->peso($summary['vat']));
        $html .= '</table>';

        // ---- GRAND TOTAL ----
        $html .= '<table width="100%" style="border-collapse:collapse;margin-top:8px;border-top:2px solid ' . self::COLOR_HEADER . ';">'
               . '<tr>'
               . '<td style="padding:9px 0 2px;font-size:15px;font-weight:700;color:' . self::COLOR_HEADER . ';">GRAND TOTAL</td>'
               . '<td style="padding:9px 0 2px;font-size:18px;font-weight:700;color:' . self::COLOR_HEADER . ';text-align:right;white-space:nowrap;">'
               . $this->peso($summary['grand_total']) . '</td>'
               . '</tr></table>';

        return $html;
    }

    /**
     * Paraan ng pagbayad, ang ibinigay na bayad, at ang sukli.
     */
    private function paymentBlock($summary)
    {
        $method = $summary['payment_method'];

        $html  = '<div style="font-size:11px;color:#6B7280;margin-bottom:4px;font-weight:600;">Payment Method:</div>';
        $html .= '<div style="font-size:12px;margin-bottom:10px;">'
               . $this->optionRow('Cash',  $method == 'Cash')
               . $this->optionRow('GCash', $method == 'GCash')
               . $this->optionRow('Card',  $method == 'Card')
               . '</div>';

        $html .= '<table width="100%" style="border-collapse:collapse;">';
        $html .= $this->totalRow('Amount Received', $this->peso($summary['amount_received']));
        $html .= $this->totalRow('Change',          $this->peso($summary['change_due']), true, self::COLOR_ACCENT);
        $html .= '</table>';

        return $html;
    }

    /** Ang pasasalamat sa ilalim ng resibo. */
    private function footerBlock()
    {
        return '<div style="text-align:center;margin-top:18px;padding-top:14px;border-top:1px dashed ' . self::COLOR_LIGHT . ';">'
             . '<div style="font-size:13px;font-weight:700;color:' . self::COLOR_HEADER . ';">Thank you for shopping at '
             . $this->e($this->store['name']) . '!</div>'
             . '<div style="font-size:10.5px;color:#9CA3AF;margin-top:4px;">'
             . 'This serves as your official receipt. Please keep it for returns and exchanges.</div>'
             . '<div style="font-size:10.5px;color:#9CA3AF;margin-top:2px;">'
             . 'All prices are VAT-inclusive. &middot; ' . $this->e($this->store['tagline']) . '</div>'
             . '</div>';
    }

    /* =================================================================
       ANG PLAIN TEXT NA RESIBO
       -----------------------------------------------------------------
       Ginagamit ito bilang "AltBody" ng e-mail — ito ang lumalabas
       kapag hindi kayang magpakita ng HTML ang e-mail app.
       ================================================================= */

    public function text()
    {
        $summary = $this->bill->summary();
        $width   = 46;
        $lines   = array();

        $center = function ($text) use ($width) {
            $text = trim($text);
            $pad  = (int) floor(($width - strlen($text)) / 2);
            if ($pad < 0) {
                $pad = 0;
            }
            return str_repeat(' ', $pad) . $text;
        };

        $money = function ($label, $amount) use ($width) {
            $value = 'PHP ' . number_format((float) $amount, 2);
            $pad   = $width - strlen($label) - strlen($value);
            if ($pad < 1) {
                $pad = 1;
            }
            return $label . str_repeat(' ', $pad) . $value;
        };

        $lines[] = str_repeat('=', $width);
        $lines[] = $center($this->store['name']);
        $lines[] = $center($this->store['tagline']);
        $lines[] = $center($this->store['address']);
        $lines[] = $center('Tel: ' . $this->store['contact']);
        $lines[] = $center($this->store['email']);
        $lines[] = str_repeat('=', $width);
        $lines[] = $center('OFFICIAL RECEIPT');
        $lines[] = str_repeat('-', $width);

        $lines[] = 'Order No. : ' . $this->orderNumber;
        $lines[] = 'Date      : ' . date('M d, Y  h:i A', strtotime($this->dateTime));

        $name    = isset($this->customer['name'])    && $this->customer['name']    != '' ? $this->customer['name']    : 'Walk-in Customer';
        $contact = isset($this->customer['contact']) && $this->customer['contact'] != '' ? $this->customer['contact'] : '-';

        $lines[] = 'Customer  : ' . $name;
        $lines[] = 'Contact   : ' . $contact;
        $lines[] = str_repeat('-', $width);

        if ($this->cashier != null) {
            $lines[] = 'Cashier     : ' . $this->cashier['employee_name'];
            $lines[] = 'Employee ID : ' . $this->cashier['employee_code'];
            $lines[] = 'Position    : ' . $this->cashier['position'];
            $lines[] = 'Shift       : ' . $this->cashier['shift'];
        } else {
            $lines[] = 'Cashier     : Not assigned';
        }
        $lines[] = str_repeat('-', $width);

        // ---- Mga binili ----
        foreach ($this->bill->items() as $item) {
            $lines[] = substr($item['product_name'], 0, $width);
            $detail  = '  ' . $item['quantity'] . ' x PHP ' . number_format($item['price'], 2);
            $lines[] = $money($detail, $item['total_price']);
        }
        $lines[] = str_repeat('-', $width);

        // ---- Discount ----
        $chosen  = $summary['discount_type'];
        $lines[] = 'Discount Type:';
        $lines[] = '  ' . ($chosen == 'Senior Citizen' ? '[X]' : '[ ]') . ' Senior Citizen'
                 . '  ' . ($chosen == 'PWD'            ? '[X]' : '[ ]') . ' PWD'
                 . '  ' . ($chosen == 'Promo'          ? '[X]' : '[ ]') . ' Promo';

        $lines[] = $money('Subtotal',        $summary['subtotal']);
        $lines[] = $money('Discount Amount', $summary['discount_amount']);
        $lines[] = $money('VATable Sales',   $summary['vatable_sales']);
        $lines[] = $money('VAT (' . round($summary['vat_rate'] * 100) . '%)', $summary['vat']);
        $lines[] = str_repeat('=', $width);
        $lines[] = $money('GRAND TOTAL',     $summary['grand_total']);
        $lines[] = str_repeat('=', $width);

        // ---- Bayad ----
        $method  = $summary['payment_method'];
        $lines[] = 'Payment Method:';
        $lines[] = '  ' . ($method == 'Cash'  ? '[X]' : '[ ]') . ' Cash'
                 . '  ' . ($method == 'GCash' ? '[X]' : '[ ]') . ' GCash'
                 . '  ' . ($method == 'Card'  ? '[X]' : '[ ]') . ' Card';

        $lines[] = $money('Amount Received', $summary['amount_received']);
        $lines[] = $money('Change',          $summary['change_due']);
        $lines[] = str_repeat('-', $width);
        $lines[] = $center('Thank you for shopping with us!');
        $lines[] = $center('All prices are VAT-inclusive.');
        $lines[] = str_repeat('=', $width);

        return implode("\n", $lines);
    }
}
