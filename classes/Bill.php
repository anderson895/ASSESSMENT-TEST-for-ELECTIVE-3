<?php
/**
 * Bill.php
 * -----------------------------------------------------------------
 * Ito ang class na gumagawa ng lahat ng KOMPUTASYON:
 *   - total ng bawat kategorya
 *   - DISCOUNT (Senior Citizen / PWD / Promo)
 *   - VAT na 12%
 *   - subtotal at grand total
 *   - amount received at SUKLI (change)
 *
 * Ang mga rate ay DOUBLE / FLOAT na data type,
 * ayon sa hinihingi ng instruction (BILL TRANSACTIONS).
 *
 * ANG PAGKAKASUNOD-SUNOD NG KOMPUTASYON:
 *   Subtotal            (kabuuan ng lahat ng binili)
 *   - Discount          (base sa subtotal)
 *   = VATable Sales
 *   + VAT 12%           (base sa VATable Sales)
 *   = GRAND TOTAL
 *   - Amount Received
 *   = CHANGE
 * -----------------------------------------------------------------
 */
class Bill
{
    /** Isang buwis lang sa buong tindahan: 12% VAT. */
    const VAT_RATE = 0.12;

    /** Rate ng bawat uri ng discount (double / float). */
    const RATE_SENIOR = 0.20;   // 20% - Senior Citizen
    const RATE_PWD    = 0.20;   // 20% - Person With Disability
    const RATE_PROMO  = 0.10;   // 10% - Store Promo

    private $products       = array();   // buong detalye ng produkto, susi = product_id
    private $items          = array();   // mga binili (may quantity)
    private $stockProblems  = array();   // mga produktong kulang ang stock
    private $discountType   = 'None';
    private $paymentMethod  = 'Cash';
    private $amountReceived = 0.00;

    /**
     * Constructor.
     * Ang $products ay galing sa Product::mapById() —
     * naglalaman ng presyo, pangalan, kategorya, stock, at larawan.
     */
    public function __construct($products)
    {
        $this->products = $products;
    }

    /* =================================================================
       PAGTANGGAP NG DATOS MULA SA WEBPAGE
       ================================================================= */

    /**
     * Ilagay sa bill ang mga binili ng customer.
     * Ang $cart ay galing sa webpage, halimbawa:
     *   [ ['product_id' => 1, 'quantity' => 2], ... ]
     *
     * Ang quantity ay INTEGER, at ang tinatanggap lang ay mas malaki sa 0.
     * Ang presyo at kategorya ay kinukuha sa DATABASE, hindi sa webpage,
     * para hindi ito mapeke ng gumagamit.
     */
    public function load($cart)
    {
        $this->items         = array();
        $this->stockProblems = array();

        if (!is_array($cart)) {
            return;
        }

        foreach ($cart as $line) {
            if (!isset($line['product_id']) || !isset($line['quantity'])) {
                continue;
            }

            $productId = (int) $line['product_id'];
            $quantity  = (int) $line['quantity'];

            // Laktawan ang walang laman o maling item.
            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }
            if (!isset($this->products[$productId])) {
                continue;
            }

            $product = $this->products[$productId];
            $price   = (float) $product['price'];
            $stock   = (int) $product['stock'];

            // Tandaan kung kulang ang stock (hindi pa ito humihinto —
            // ang api/save_order.php ang magpapasya kung ipagpapatuloy).
            if ($quantity > $stock) {
                $this->stockProblems[] = array(
                    'product_id'   => $productId,
                    'product_name' => $product['product_name'],
                    'requested'    => $quantity,
                    'stock'        => $stock
                );
            }

            // Itago ang detalye ng bawat binili.
            $this->items[] = array(
                'product_id'   => $productId,
                'product_name' => $product['product_name'],
                'category'     => $product['category'],
                'image'        => $product['image'],
                'quantity'     => $quantity,
                'price'        => $price,
                'stock'        => $stock,
                'total_price'  => round($price * $quantity, 2)
            );
        }
    }

    /**
     * Itakda ang uri ng discount.
     * Tinatanggap lang: 'Senior Citizen', 'PWD', 'Promo'.
     * Kahit ano pang iba ay magiging 'None'.
     */
    public function setDiscount($type)
    {
        $type = trim((string) $type);

        if ($type == 'Senior Citizen' || $type == 'PWD' || $type == 'Promo') {
            $this->discountType = $type;
        } else {
            $this->discountType = 'None';
        }
    }

    /**
     * Itakda ang paraan ng pagbayad at kung magkano ang ibinigay.
     * Tinatanggap lang: 'Cash', 'GCash', 'Card'.
     */
    public function setPayment($method, $amountReceived)
    {
        $method = trim((string) $method);

        if ($method == 'Cash' || $method == 'GCash' || $method == 'Card') {
            $this->paymentMethod = $method;
        } else {
            $this->paymentMethod = 'Cash';
        }

        $amount = (float) $amountReceived;
        if ($amount < 0) {
            $amount = 0.00;
        }
        $this->amountReceived = round($amount, 2);
    }

    /* =================================================================
       KOMPUTASYON
       ================================================================= */

    /**
     * TOTAL NG BAWAT KATEGORYA.
     * Halimbawa: [ 'Grocery' => 250.00, 'Beverages' => 60.00, ... ]
     */
    public function categoryTotals()
    {
        $totals = array();

        // Idagdag ang halaga ng bawat binili sa tamang kategorya.
        foreach ($this->items as $item) {
            $category = $item['category'];

            if (!isset($totals[$category])) {
                $totals[$category] = 0.00;
            }
            $totals[$category] = $totals[$category] + $item['total_price'];
        }

        // I-round sa 2 decimal places.
        foreach ($totals as $category => $amount) {
            $totals[$category] = round($amount, 2);
        }

        return $totals;
    }

    /**
     * SUBTOTAL = kabuuan ng lahat ng binili (wala pang discount at VAT).
     */
    public function subtotal()
    {
        $sum = 0.00;

        foreach ($this->items as $item) {
            $sum = $sum + $item['total_price'];
        }

        return round($sum, 2);
    }

    /** Anong uri ng discount ang napili. */
    public function discountType()
    {
        return $this->discountType;
    }

    /**
     * Ang rate ng napiling discount (double / float).
     * Walang napili = 0.0
     */
    public function discountRate()
    {
        if ($this->discountType == 'Senior Citizen') {
            return self::RATE_SENIOR;
        }
        if ($this->discountType == 'PWD') {
            return self::RATE_PWD;
        }
        if ($this->discountType == 'Promo') {
            return self::RATE_PROMO;
        }
        return 0.0;
    }

    /**
     * DISCOUNT AMOUNT = subtotal x discount rate
     */
    public function discountAmount()
    {
        return round($this->subtotal() * $this->discountRate(), 2);
    }

    /**
     * VATABLE SALES = subtotal - discount
     * Ito ang halagang pinapatawan ng 12% VAT.
     */
    public function vatableSales()
    {
        $net = $this->subtotal() - $this->discountAmount();

        if ($net < 0) {
            $net = 0.00;
        }
        return round($net, 2);
    }

    /**
     * VAT = VATable Sales x 12%
     */
    public function vat()
    {
        return round($this->vatableSales() * self::VAT_RATE, 2);
    }

    /**
     * GRAND TOTAL = VATable Sales + VAT
     */
    public function grandTotal()
    {
        return round($this->vatableSales() + $this->vat(), 2);
    }

    /** Paraan ng pagbayad. */
    public function paymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * Magkano ang ibinigay na bayad.
     *
     * Sa GCash at Card, EKSAKTO ang nasisingil — kaya ang bayad ay
     * kapareho ng grand total, at walang sukli. Sa Cash lang totoong
     * may itinatype na halaga ang cashier.
     */
    public function amountReceived()
    {
        if ($this->paymentMethod != 'Cash') {
            return $this->grandTotal();
        }
        return $this->amountReceived;
    }

    /**
     * SUKLI = amount received - grand total.
     * Hindi kailanman negatibo ang ipapakita.
     */
    public function changeDue()
    {
        $change = $this->amountReceived() - $this->grandTotal();

        if ($change < 0) {
            $change = 0.00;
        }
        return round($change, 2);
    }

    /**
     * Sapat ba ang bayad?
     * Sa Cash, kailangang kasing-laki o mas malaki pa sa grand total.
     * Sa GCash at Card, ang buong halaga ang awtomatikong sinisingil.
     */
    public function isPaid()
    {
        if ($this->paymentMethod != 'Cash') {
            return true;
        }
        return $this->amountReceived() >= $this->grandTotal();
    }

    /** Ibalik ang listahan ng mga binili. */
    public function items()
    {
        return $this->items;
    }

    /** Mga produktong lumampas sa natitirang stock. */
    public function stockProblems()
    {
        return $this->stockProblems;
    }

    /** Ilan lahat ang piraso na binili. */
    public function totalQuantity()
    {
        $count = 0;

        foreach ($this->items as $item) {
            $count = $count + $item['quantity'];
        }
        return $count;
    }

    /**
     * Buong resulta ng komputasyon - ipapadala sa webpage at resibo.
     */
    public function summary()
    {
        return array(
            'category_totals' => $this->categoryTotals(),
            'subtotal'        => $this->subtotal(),
            'discount_type'   => $this->discountType(),
            'discount_rate'   => $this->discountRate(),
            'discount_amount' => $this->discountAmount(),
            'vatable_sales'   => $this->vatableSales(),
            'vat_rate'        => self::VAT_RATE,
            'vat'             => $this->vat(),
            'grand_total'     => $this->grandTotal(),
            'payment_method'  => $this->paymentMethod(),
            'amount_received' => $this->amountReceived(),
            'change_due'      => $this->changeDue(),
            'is_paid'         => $this->isPaid(),
            'total_quantity'  => $this->totalQuantity(),
            'items'           => $this->items
        );
    }
}
