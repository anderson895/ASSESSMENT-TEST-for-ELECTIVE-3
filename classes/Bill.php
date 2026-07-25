<?php
/**
 * Bill.php
 * -----------------------------------------------------------------
 * Ito ang class na gumagawa ng lahat ng KOMPUTASYON:
 *   - total ng bawat kategorya
 *   - buwis (tax) ng bawat kategorya
 *   - subtotal, total tax, at grand total
 *
 * Ang mga tax rate ay DOUBLE / FLOAT na data type,
 * ayon sa hinihingi ng instruction (BILL TRANSACTIONS).
 * -----------------------------------------------------------------
 */
class Bill
{
    // Tax rate ng bawat kategorya (double / float na halaga).
    const TAX_BEAUTY    = 0.12;   // 12%
    const TAX_GROCERY   = 0.02;   //  2%
    const TAX_BEVERAGES = 0.05;   //  5%

    private $priceList = array();   // presyo ng bawat produkto
    private $items     = array();   // mga binili (may quantity)

    /**
     * Constructor - kailangan ng listahan ng presyo galing sa Product class.
     */
    public function __construct($priceList)
    {
        $this->priceList = $priceList;
    }

    /**
     * Ibalik ang tax rate ng isang kategorya.
     */
    public function taxRate($category)
    {
        if ($category == 'Beauty & Personal Care') {
            return self::TAX_BEAUTY;
        }
        if ($category == 'Grocery') {
            return self::TAX_GROCERY;
        }
        if ($category == 'Beverages') {
            return self::TAX_BEVERAGES;
        }
        return 0.0;   // hindi kilalang kategorya
    }

    /**
     * Ilagay sa bill ang mga binili ng customer.
     * Ang $cart ay galing sa webpage, halimbawa:
     *   [ ['product_id' => 1, 'category' => 'Grocery', 'quantity' => 2], ... ]
     *
     * Ang quantity ay INTEGER, at ang tinatanggap lang ay mas malaki sa 0.
     */
    public function load($cart)
    {
        $this->items = array();

        foreach ($cart as $line) {
            $productId = (int) $line['product_id'];
            $quantity  = (int) $line['quantity'];
            $category  = $line['category'];

            // Laktawan ang walang laman o maling item.
            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }
            if (!isset($this->priceList[$productId])) {
                continue;
            }

            $price = (float) $this->priceList[$productId];
            $total = $price * $quantity;

            // Itago ang detalye ng bawat binili.
            $this->items[] = array(
                'product_id'  => $productId,
                'category'    => $category,
                'quantity'    => $quantity,
                'price'       => $price,
                'total_price' => round($total, 2)
            );
        }
    }

    /**
     * TOTAL NG BAWAT KATEGORYA.
     * Halimbawa: [ 'Grocery' => 250.00, 'Beverages' => 60.00, ... ]
     */
    public function categoryTotals()
    {
        // Simulan sa 0.00 ang tatlong kategorya.
        $totals = array(
            'Beauty & Personal Care' => 0.00,
            'Grocery'                => 0.00,
            'Beverages'              => 0.00
        );

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
     * BUWIS NG BAWAT KATEGORYA.
     * Formula: total ng kategorya x tax rate
     */
    public function categoryTaxes()
    {
        $taxes = array();

        foreach ($this->categoryTotals() as $category => $total) {
            $rate             = $this->taxRate($category);
            $taxes[$category] = round($total * $rate, 2);
        }

        return $taxes;
    }

    /**
     * SUBTOTAL = kabuuan ng lahat ng kategorya (wala pang buwis).
     */
    public function subtotal()
    {
        $sum = 0.00;

        foreach ($this->categoryTotals() as $total) {
            $sum = $sum + $total;
        }

        return round($sum, 2);
    }

    /**
     * TOTAL TAX = kabuuan ng lahat ng buwis.
     */
    public function totalTax()
    {
        $sum = 0.00;

        foreach ($this->categoryTaxes() as $tax) {
            $sum = $sum + $tax;
        }

        return round($sum, 2);
    }

    /**
     * GRAND TOTAL = subtotal + total tax
     */
    public function grandTotal()
    {
        return round($this->subtotal() + $this->totalTax(), 2);
    }

    /**
     * Ibalik ang listahan ng mga binili.
     */
    public function items()
    {
        return $this->items;
    }

    /**
     * Buong resulta ng komputasyon - ipapadala sa webpage at resibo.
     */
    public function summary()
    {
        return array(
            'category_totals' => $this->categoryTotals(),
            'category_taxes'  => $this->categoryTaxes(),
            'tax_rates'       => array(
                'Beauty & Personal Care' => self::TAX_BEAUTY,
                'Grocery'                => self::TAX_GROCERY,
                'Beverages'              => self::TAX_BEVERAGES
            ),
            'subtotal'    => $this->subtotal(),
            'total_tax'   => $this->totalTax(),
            'grand_total' => $this->grandTotal(),
            'items'       => $this->items
        );
    }
}
