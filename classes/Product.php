<?php
/**
 * Product.php
 * -----------------------------------------------------------------
 * Class para sa mga produkto. Dito kinukuha ang listahan ng produkto
 * mula sa database (products table).
 * -----------------------------------------------------------------
 */
class Product
{
    private $db;   // koneksyon sa database

    /**
     * Constructor - tumatakbo tuwing gagawa ng bagong Product object.
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Kunin ang LAHAT ng produkto, naka-ayos ayon sa kategorya.
     */
    public function all()
    {
        $sql = "SELECT product_id, category, product_name, price
                FROM products
                ORDER BY category, product_id";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Pagpangkat-pangkatin ang produkto ayon sa kategorya.
     * Halimbawa ng ibabalik:
     *   [ 'Grocery' => [ Rice, Eggs, ... ], 'Beverages' => [ ... ] ]
     */
    public function grouped()
    {
        $grouped = array();

        foreach ($this->all() as $product) {
            $category = $product['category'];
            $grouped[$category][] = $product;
        }

        return $grouped;
    }

    /**
     * Gumawa ng listahan ng presyo kung saan ang susi ay product_id.
     * Halimbawa: [ 1 => 149.75, 2 => 129.50, ... ]
     * Ginagamit ito ng Bill class para malaman ang presyo ng bawat item.
     */
    public function priceMap()
    {
        $prices = array();

        foreach ($this->all() as $product) {
            $id            = (int) $product['product_id'];
            $prices[$id]   = (float) $product['price'];
        }

        return $prices;
    }
}
