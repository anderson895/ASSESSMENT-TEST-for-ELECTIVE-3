<?php
/**
 * Product.php
 * Data access for products, grouped by category.
 */
class Product
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Return every product ordered by category then name.
     */
    public function all(): array
    {
        $sql = "SELECT product_id, category, product_name, price
                FROM products
                ORDER BY category, product_id";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Return products grouped into an associative array keyed by category:
     *   [ 'Grocery' => [ {product}, {product} ], ... ]
     */
    public function grouped(): array
    {
        $grouped = [];
        foreach ($this->all() as $product) {
            $grouped[$product['category']][] = $product;
        }
        return $grouped;
    }

    /**
     * Return a price lookup map keyed by product_id.
     */
    public function priceMap(): array
    {
        $map = [];
        foreach ($this->all() as $product) {
            $map[(int) $product['product_id']] = (float) $product['price'];
        }
        return $map;
    }
}
