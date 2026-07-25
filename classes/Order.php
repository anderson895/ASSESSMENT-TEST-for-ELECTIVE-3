<?php
/**
 * Order.php
 * -----------------------------------------------------------------
 * Class na nag-i-save ng order sa database.
 * Dalawang table ang pinupuno nito:
 *   orders       - ang buod (subtotal, tax, grand total)
 *   order_items  - ang bawat produktong binili
 * -----------------------------------------------------------------
 */
class Order
{
    private $db;   // koneksyon sa database

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * I-save ang order at ibalik ang bagong order_id.
     *
     * $customerId = ID ng customer
     * $bill       = Bill object na may kompletong komputasyon
     */
    public function save($customerId, $bill)
    {
        // Gumagamit tayo ng TRANSACTION para siguradong
        // kompleto ang pag-save. Kung may mali kahit isa,
        // babalik sa dati ang lahat (walang kalahating record).
        $this->db->beginTransaction();

        try {
            // ---- 1) I-save ang buod ng order ----
            $sql = "INSERT INTO orders (customer_id, subtotal, total_tax, grand_total)
                    VALUES (:cid, :sub, :tax, :grand)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(array(
                ':cid'   => $customerId,
                ':sub'   => $bill->subtotal(),
                ':tax'   => $bill->totalTax(),
                ':grand' => $bill->grandTotal()
            ));

            // Kunin ang ID ng bagong naisave na order.
            $orderId = (int) $this->db->lastInsertId();

            // ---- 2) I-save ang bawat produktong binili ----
            $sqlItem = "INSERT INTO order_items (order_id, product_id, quantity, total_price)
                        VALUES (:oid, :pid, :qty, :total)";

            $stmtItem = $this->db->prepare($sqlItem);

            foreach ($bill->items() as $item) {
                $stmtItem->execute(array(
                    ':oid'   => $orderId,
                    ':pid'   => $item['product_id'],
                    ':qty'   => $item['quantity'],
                    ':total' => $item['total_price']
                ));
            }

            // Kumpleto - i-save na talaga sa database.
            $this->db->commit();

            return $orderId;

        } catch (Exception $e) {
            // May mali - ibalik sa dati ang database.
            $this->db->rollBack();
            throw $e;
        }
    }
}
