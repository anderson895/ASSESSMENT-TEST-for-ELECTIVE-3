<?php
/**
 * Product.php
 * -----------------------------------------------------------------
 * Class para sa mga produkto. Dito kinukuha ang listahan ng produkto
 * mula sa database (products table), kasama ang LARAWAN at STOCK.
 *
 * Kinukuha rin nito ang larawan ng bawat KATEGORYA mula sa
 * categories table.
 * -----------------------------------------------------------------
 */
class Product
{
    /** Larawang gagamitin kapag walang nakatakdang larawan ang produkto. */
    const FALLBACK_IMAGE = 'assets/img/products/placeholder.svg';

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
     * Kasama ang larawan at ang natitirang stock.
     */
    public function all()
    {
        $sql = "SELECT p.product_id,
                       p.category,
                       p.product_name,
                       p.price,
                       p.stock,
                       p.image,
                       c.image      AS category_image,
                       c.sort_order AS category_order
                  FROM products p
             LEFT JOIN categories c ON c.category_name = p.category
              ORDER BY COALESCE(c.sort_order, 999), p.category, p.product_id";

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();

        // Siguraduhing may larawan at tamang data type ang bawat produkto.
        foreach ($rows as $index => $row) {
            $rows[$index]['price'] = (float) $row['price'];
            $rows[$index]['stock'] = (int) $row['stock'];

            if ($row['image'] == '' || $row['image'] == null) {
                $rows[$index]['image'] = self::FALLBACK_IMAGE;
            }
        }

        return $rows;
    }

    /**
     * Pagpangkat-pangkatin ang produkto ayon sa kategorya.
     * Ang bawat pangkat ay may sariling larawan at listahan ng produkto:
     *   [ 'Grocery' => [ 'image' => '...', 'products' => [ ... ] ], ... ]
     */
    public function grouped()
    {
        $grouped = array();

        foreach ($this->all() as $product) {
            $category = $product['category'];

            if (!isset($grouped[$category])) {
                $image = $product['category_image'];

                if ($image == '' || $image == null) {
                    $image = self::FALLBACK_IMAGE;
                }

                $grouped[$category] = array(
                    'name'     => $category,
                    'image'    => $image,
                    'products' => array()
                );
            }

            $grouped[$category]['products'][] = $product;
        }

        return $grouped;
    }

    /**
     * Gumawa ng listahan ng produkto kung saan ang susi ay product_id.
     * Halimbawa: [ 1 => ['product_name' => 'Facial Cleanser', 'price' => 149.75, ...], ... ]
     *
     * Ginagamit ito ng Bill class para malaman ang presyo, kategorya,
     * stock, at larawan ng bawat item — galing mismo sa database,
     * hindi sa webpage, para hindi mapeke.
     */
    public function mapById()
    {
        $map = array();

        foreach ($this->all() as $product) {
            $map[(int) $product['product_id']] = $product;
        }

        return $map;
    }

    /**
     * IBAWAS ang binili sa natitirang stock.
     * Ginagamit ito ng Order class tuwing may na-save na bill.
     *
     * Ang `stock >= :needed` ay panghuling proteksyon: kahit dalawang
     * cashier ang sabay na nag-bill, hindi mananaog sa zero ang stock.
     */
    public function reduceStock($productId, $quantity)
    {
        $sql = "UPDATE products
                   SET stock = stock - :qty
                 WHERE product_id = :pid
                   AND stock >= :needed";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':qty'    => (int) $quantity,
            ':pid'    => (int) $productId,
            ':needed' => (int) $quantity
        ));

        // Kung walang naapektuhang row, ibig sabihin kulang na ang stock.
        return $stmt->rowCount() > 0;
    }
}
