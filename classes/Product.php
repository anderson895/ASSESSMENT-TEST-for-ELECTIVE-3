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

    /* =================================================================
       PAMAMAHALA NG PRODUKTO (ginagamit ng products.php)
       ================================================================= */

    /**
     * Kunin ang listahan ng lahat ng kategorya mula sa categories table.
     * Ito ang laman ng dropdown sa Add Product form.
     */
    public function categories()
    {
        $sql = "SELECT category_name, image
                  FROM categories
              ORDER BY sort_order, category_name";

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Kunin ang isang produkto gamit ang product_id.
     * Ibabalik ang null kapag wala.
     */
    public function find($productId)
    {
        $sql = "SELECT product_id, category, product_name, price, stock, image
                  FROM products
                 WHERE product_id = :pid";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':pid' => (int) $productId));

        $row = $stmt->fetch();

        if ($row == false) {
            return null;
        }

        $row['price'] = (float) $row['price'];
        $row['stock'] = (int) $row['stock'];

        return $row;
    }

    /**
     * Tingnan kung may produkto nang ganito ang pangalan sa parehong kategorya.
     * Ang $ignoreId ay ang produktong ini-edit (para hindi nito makita ang sarili).
     */
    public function nameExists($category, $productName, $ignoreId = 0)
    {
        $sql = "SELECT product_id
                  FROM products
                 WHERE category     = :cat
                   AND product_name = :name
                   AND product_id  <> :ignore
                 LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':cat'    => (string) $category,
            ':name'   => (string) $productName,
            ':ignore' => (int) $ignoreId
        ));

        return $stmt->fetch() != false;
    }

    /**
     * MAGDAGDAG ng bagong produkto.
     * Ibabalik ang product_id ng bagong nagawa.
     */
    public function create($category, $productName, $price, $stock, $image)
    {
        $sql = "INSERT INTO products (category, product_name, price, stock, image)
                VALUES (:cat, :name, :price, :stock, :image)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':cat'   => (string) $category,
            ':name'  => (string) $productName,
            ':price' => (float) $price,
            ':stock' => (int) $stock,
            ':image' => ($image == '' ? null : (string) $image)
        ));

        return (int) $this->db->lastInsertId();
    }

    /**
     * I-UPDATE ang isang produkto.
     *
     * Kapag ang $image ay null, hindi gagalawin ang dating larawan —
     * para hindi mawala ang litrato kapag presyo lang ang binago.
     */
    public function update($productId, $category, $productName, $price, $stock, $image = null)
    {
        $fields = array(
            'category'     => (string) $category,
            'product_name' => (string) $productName,
            'price'        => (float) $price,
            'stock'        => (int) $stock
        );

        if ($image !== null) {
            $fields['image'] = (string) $image;
        }

        // Buuin ang "column = :column" na bahagi ng SQL.
        $sets   = array();
        $params = array(':pid' => (int) $productId);

        foreach ($fields as $column => $value) {
            $sets[]                = $column . ' = :' . $column;
            $params[':' . $column] = $value;
        }

        $sql = "UPDATE products SET " . implode(', ', $sets) . " WHERE product_id = :pid";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return true;
    }

    /**
     * Ilang beses nang naibenta ang produktong ito?
     *
     * Kailangan itong tingnan bago mag-delete: ang order_items ay may
     * FOREIGN KEY papunta sa products, kaya hindi puwedeng burahin ang
     * produktong may kasaysayan — mawawala ang lumang resibo.
     */
    public function timesSold($productId)
    {
        $sql = "SELECT COUNT(*) FROM order_items WHERE product_id = :pid";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':pid' => (int) $productId));

        return (int) $stmt->fetchColumn();
    }

    /**
     * BURAHIN ang produkto.
     * Gamitin lang kapag 0 ang timesSold() — kung hindi, tanggihan sa API.
     */
    public function delete($productId)
    {
        $stmt = $this->db->prepare("DELETE FROM products WHERE product_id = :pid");
        $stmt->execute(array(':pid' => (int) $productId));

        return $stmt->rowCount() > 0;
    }
}
