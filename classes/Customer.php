<?php
/**
 * Customer.php
 * Encapsulates all customer-related data access (OOP model).
 */
class Customer
{
    private PDO $db;

    public int $customer_id = 0;
    public string $customer_name = '';
    public string $contact_number = '';
    public string $order_number = '';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Find a customer by Contact Number OR Order Number.
     * Returns the customer row as an associative array, or null if not found.
     */
    public function find(string $keyword): ?array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return null;
        }

        $sql = "SELECT customer_id, customer_name, contact_number, order_number
                FROM customers
                WHERE contact_number = :contact OR order_number = :orderNo
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':contact' => $keyword, ':orderNo' => $keyword]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Persist a customer. If the order_number already exists, reuse that record.
     * Returns the customer_id.
     */
    public function save(string $name, string $contact, string $orderNo): int
    {
        $name    = trim($name);
        $contact = trim($contact);
        $orderNo = trim($orderNo);

        // Reuse an existing customer: match by order number first, then by
        // contact number, so we never create duplicate rows for the same person.
        $existing = null;
        if ($orderNo !== '') {
            $existing = $this->find($orderNo);
        }
        if (!$existing && $contact !== '') {
            $existing = $this->find($contact);
        }
        if ($existing) {
            return (int) $existing['customer_id'];
        }

        // Brand-new customer. The order number is supplied manually by the user
        // (validated as required in api/save_order.php before we reach here).
        $sql = "INSERT INTO customers (customer_name, contact_number, order_number)
                VALUES (:name, :contact, :orderNo)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name'    => $name !== '' ? $name : 'Walk-in Customer',
            ':contact' => $contact,
            ':orderNo' => $orderNo,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
