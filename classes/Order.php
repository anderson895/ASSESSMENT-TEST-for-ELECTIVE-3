<?php
/**
 * Order.php
 * Persists an order and its line items inside a transaction.
 */
class Order
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Save an order header + its items.
     *
     * @param int|null $customerId
     * @param Bill     $bill  a Bill already loaded with the cart
     * @return int the new order_id
     */
    public function save(?int $customerId, Bill $bill): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO orders (customer_id, subtotal, total_tax, grand_total)
                 VALUES (:cid, :sub, :tax, :grand)"
            );
            $stmt->execute([
                ':cid'   => $customerId ?: null,
                ':sub'   => $bill->subtotal(),
                ':tax'   => $bill->totalTax(),
                ':grand' => $bill->grandTotal(),
            ]);
            $orderId = (int) $this->db->lastInsertId();

            $itemStmt = $this->db->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity, total_price)
                 VALUES (:oid, :pid, :qty, :total)"
            );
            foreach ($bill->items() as $item) {
                $itemStmt->execute([
                    ':oid'   => $orderId,
                    ':pid'   => $item['product_id'],
                    ':qty'   => $item['quantity'],
                    ':total' => $item['total_price'],
                ]);
            }

            $this->db->commit();
            return $orderId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
