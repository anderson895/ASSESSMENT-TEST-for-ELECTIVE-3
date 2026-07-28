<?php
/**
 * Order.php
 * -----------------------------------------------------------------
 * Class na nag-i-save ng order sa database.
 * Dalawang table ang pinupuno nito:
 *   orders       - ang buod (cashier, bayad, discount, VAT, total)
 *   order_items  - ang bawat produktong binili
 *
 * Dito rin ginagawa ang AUTOMATIC na ORDER NUMBER.
 * Ang porma nito ay:  ORD-YYYYMMDD-####
 * Halimbawa:          ORD-20260728-0001
 * Araw-araw itong bumabalik sa 0001.
 * -----------------------------------------------------------------
 */
class Order
{
    /** Ilang beses susubukan kapag nag-sabay ang dalawang cashier. */
    const MAX_ATTEMPTS = 5;

    private $db;   // koneksyon sa database

    public function __construct($db)
    {
        $this->db = $db;
    }

    /* =================================================================
       AUTOMATIC NA ORDER NUMBER
       ================================================================= */

    /**
     * Ano ang SUSUNOD na order number?
     *
     * Tinitingnan nito ang pinakahuling order ngayong araw, tapos
     * dinadagdagan ng isa. Kapag wala pang order ngayong araw,
     * magsisimula sa 0001.
     */
    public function nextOrderNumber()
    {
        $prefix = 'ORD-' . date('Ymd') . '-';

        $sql = "SELECT order_number
                  FROM orders
                 WHERE order_number LIKE :pattern
              ORDER BY order_number DESC
                 LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':pattern' => $prefix . '%'));

        $row = $stmt->fetch();

        // Wala pang order ngayong araw - simulan sa 1.
        if ($row == false) {
            $next = 1;
        } else {
            // Kunin ang huling 4 na digit, tapos dagdagan ng isa.
            $lastCount = (int) substr($row['order_number'], strlen($prefix));
            $next      = $lastCount + 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /* =================================================================
       PAG-SAVE NG ORDER
       ================================================================= */

    /**
     * I-save ang order at ibalik ang order_id at order_number.
     *
     * $customerId = ID ng customer
     * $employeeId = ID ng cashier na nag-asikaso (pwedeng null)
     * $bill       = Bill object na may kompletong komputasyon
     */
    public function save($customerId, $employeeId, $bill)
    {
        // Gumagamit tayo ng TRANSACTION para siguradong kompleto ang
        // pag-save. Kung may mali kahit isa, babalik sa dati ang lahat
        // (walang kalahating record, at hindi mababawasan ang stock).
        $this->db->beginTransaction();

        try {
            // ---- 1) I-save ang buod ng order ----
            $orderNumber = $this->insertOrder($customerId, $employeeId, $bill);

            // Kunin ang ID ng bagong naisave na order.
            $orderId = (int) $this->db->lastInsertId();

            // ---- 2) I-save ang bawat produktong binili ----
            $sqlItem = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price)
                        VALUES (:oid, :pid, :qty, :unit, :total)";

            $stmtItem     = $this->db->prepare($sqlItem);
            $productModel = new Product($this->db);

            foreach ($bill->items() as $item) {
                $stmtItem->execute(array(
                    ':oid'   => $orderId,
                    ':pid'   => $item['product_id'],
                    ':qty'   => $item['quantity'],
                    ':unit'  => $item['price'],
                    ':total' => $item['total_price']
                ));

                // ---- 3) Ibawas ang binili sa stock ----
                $reduced = $productModel->reduceStock($item['product_id'], $item['quantity']);

                if (!$reduced) {
                    throw new RuntimeException(
                        'Not enough stock left for "' . $item['product_name'] . '". '
                        . 'Please refresh the page and check the remaining stock.'
                    );
                }
            }

            // Kumpleto - i-save na talaga sa database.
            $this->db->commit();

            return array(
                'order_id'     => $orderId,
                'order_number' => $orderNumber
            );

        } catch (Exception $e) {
            // May mali - ibalik sa dati ang database.
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * I-insert ang orders row gamit ang bagong order number.
     *
     * Kapag nag-sabay ang dalawang cashier, posibleng magkapareho ang
     * nakuha nilang order number. Kapag nangyari iyon, susubok ulit ito
     * ng bagong numero — kaya walang duplicate na lalabas.
     */
    private function insertOrder($customerId, $employeeId, $bill)
    {
        $sql = "INSERT INTO orders
                    (order_number, customer_id, employee_id, payment_method,
                     discount_type, discount_amount, subtotal, total_tax,
                     grand_total, amount_received, change_due)
                VALUES
                    (:no, :cid, :eid, :method,
                     :dtype, :damount, :sub, :vat,
                     :grand, :received, :change)";

        $stmt = $this->db->prepare($sql);

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $orderNumber = $this->nextOrderNumber();

            try {
                $stmt->execute(array(
                    ':no'       => $orderNumber,
                    ':cid'      => $customerId,
                    ':eid'      => $employeeId,
                    ':method'   => $bill->paymentMethod(),
                    ':dtype'    => $bill->discountType(),
                    ':damount'  => $bill->discountAmount(),
                    ':sub'      => $bill->subtotal(),
                    ':vat'      => $bill->vat(),
                    ':grand'    => $bill->grandTotal(),
                    ':received' => $bill->amountReceived(),
                    ':change'   => $bill->changeDue()
                ));

                return $orderNumber;

            } catch (PDOException $e) {
                // 23000 = may kaparehong order number na (unique key).
                // Kung ibang klaseng error, ipasa na natin sa taas.
                if ($e->getCode() != '23000') {
                    throw $e;
                }
            }
        }

        throw new RuntimeException(
            'Could not generate a unique order number. Please try again.'
        );
    }

    /* =================================================================
       ORDER HISTORY (FIND button)
       ================================================================= */

    /**
     * Kunin ang LUMA/NAKARAANG mga order ng isang customer.
     *
     * Ang ibabalik ay listahan ng order. Ang bawat order ay may
     * sariling listahan ng mga produktong binili ('items').
     * Ang pinakabago ang nauuna.
     */
    public function historyByCustomer($customerId)
    {
        $customerId = (int) $customerId;

        // ---- 1) Kunin ang buod ng bawat order ----
        $sql = "SELECT o.order_id,
                       o.order_number,
                       o.payment_method,
                       o.discount_type,
                       o.discount_amount,
                       o.subtotal,
                       o.total_tax,
                       o.grand_total,
                       o.amount_received,
                       o.change_due,
                       o.created_at,
                       e.employee_code,
                       e.employee_name,
                       e.position,
                       e.shift
                  FROM orders o
             LEFT JOIN employees e ON e.employee_id = o.employee_id
                 WHERE o.customer_id = :cid
              ORDER BY o.order_id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':cid' => $customerId));
        $orders = $stmt->fetchAll();

        // Walang dating order.
        if ($orders == false) {
            return array();
        }

        // ---- 2) Kunin ang mga produkto ng bawat order ----
        $sqlItems = "SELECT p.product_name, p.category, p.image,
                            i.quantity, i.unit_price, i.total_price
                       FROM order_items i
                       JOIN products p ON p.product_id = i.product_id
                      WHERE i.order_id = :oid
                   ORDER BY p.category, p.product_name";

        $stmtItems = $this->db->prepare($sqlItems);

        $history = array();

        foreach ($orders as $order) {
            $stmtItems->execute(array(':oid' => $order['order_id']));
            $items = $stmtItems->fetchAll();

            $history[] = array(
                'order_id'        => (int) $order['order_id'],
                'order_number'    => $order['order_number'],
                'payment_method'  => $order['payment_method'],
                'discount_type'   => $order['discount_type'],
                'discount_amount' => (float) $order['discount_amount'],
                'subtotal'        => (float) $order['subtotal'],
                'total_tax'       => (float) $order['total_tax'],
                'grand_total'     => (float) $order['grand_total'],
                'amount_received' => (float) $order['amount_received'],
                'change_due'      => (float) $order['change_due'],
                'created_at'      => $order['created_at'],
                'cashier'         => array(
                    'employee_code' => $order['employee_code'],
                    'employee_name' => $order['employee_name'],
                    'position'      => $order['position'],
                    'shift'         => $order['shift']
                ),
                'items'           => $items
            );
        }

        return $history;
    }

    /**
     * Hanapin ang customer_id na may ganitong order number.
     * Ginagamit ng FIND button para makahanap gamit ang order number
     * na nakalimbag sa resibo.
     */
    public function customerIdByOrderNumber($orderNumber)
    {
        $orderNumber = trim($orderNumber);

        if ($orderNumber == '') {
            return null;
        }

        $sql = "SELECT customer_id
                  FROM orders
                 WHERE order_number = :no
                 LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':no' => $orderNumber));

        $row = $stmt->fetch();

        if ($row == false || $row['customer_id'] == null) {
            return null;
        }
        return (int) $row['customer_id'];
    }
}
