<?php
/**
 * Bill.php
 * Core billing logic (OOP). Computes category totals, per-category taxes
 * (double / float), subtotal, total tax and grand total.
 *
 * Tax rates are declared as float constants to satisfy the requirement that
 * taxes use a double / float data type.
 */
class Bill
{
    /** Per-category tax rates (float). */
    public const TAX_RATES = [
        'Beauty & Personal Care' => 0.12,  // 12%
        'Grocery'                => 0.02,  //  2%
        'Beverages'              => 0.05,  //  5%
    ];

    private array $priceMap;   // product_id => price
    private array $items = []; // computed line items

    public function __construct(array $priceMap)
    {
        $this->priceMap = $priceMap;
    }

    /**
     * Feed the bill with the raw cart:
     *   $cart = [ ['product_id'=>1,'category'=>'Grocery','quantity'=>2], ... ]
     * Only positive integer quantities are kept.
     */
    public function load(array $cart): void
    {
        $this->items = [];
        foreach ($cart as $line) {
            $pid = (int) ($line['product_id'] ?? 0);
            $qty = (int) ($line['quantity'] ?? 0);
            $cat = (string) ($line['category'] ?? '');

            if ($pid <= 0 || $qty <= 0 || !isset($this->priceMap[$pid])) {
                continue;
            }

            $price = (float) $this->priceMap[$pid];
            $this->items[] = [
                'product_id' => $pid,
                'category'   => $cat,
                'quantity'   => $qty,
                'price'      => $price,
                'total_price'=> round($price * $qty, 2),
            ];
        }
    }

    /**
     * Category totals => [ 'Grocery' => 250.00, ... ]
     * Every known category is returned (0.00 when empty).
     */
    public function categoryTotals(): array
    {
        $totals = array_fill_keys(array_keys(self::TAX_RATES), 0.00);
        foreach ($this->items as $item) {
            $cat = $item['category'];
            if (!isset($totals[$cat])) {
                $totals[$cat] = 0.00;
            }
            $totals[$cat] += $item['total_price'];
        }
        return array_map(fn ($v) => round($v, 2), $totals);
    }

    /**
     * Per-category taxes (float) => [ 'Grocery' => 5.00, ... ]
     */
    public function categoryTaxes(): array
    {
        $taxes = [];
        foreach ($this->categoryTotals() as $cat => $total) {
            $rate = self::TAX_RATES[$cat] ?? 0.0;
            $taxes[$cat] = round($total * $rate, 2);
        }
        return $taxes;
    }

    public function subtotal(): float
    {
        return round(array_sum($this->categoryTotals()), 2);
    }

    public function totalTax(): float
    {
        return round(array_sum($this->categoryTaxes()), 2);
    }

    public function grandTotal(): float
    {
        return round($this->subtotal() + $this->totalTax(), 2);
    }

    public function items(): array
    {
        return $this->items;
    }

    /**
     * Full summary payload for the frontend / receipt.
     */
    public function summary(): array
    {
        return [
            'category_totals' => $this->categoryTotals(),
            'category_taxes'  => $this->categoryTaxes(),
            'tax_rates'       => self::TAX_RATES,
            'subtotal'        => $this->subtotal(),
            'total_tax'       => $this->totalTax(),
            'grand_total'     => $this->grandTotal(),
            'items'           => $this->items,
        ];
    }
}
