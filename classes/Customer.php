<?php
/**
 * Customer.php
 * -----------------------------------------------------------------
 * Class para sa customer. Dito ginagawa ang paghahanap (Find button)
 * at ang pag-save ng customer sa database.
 * -----------------------------------------------------------------
 */
class Customer
{
    private $db;   // koneksyon sa database

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * FIND BUTTON.
     * Hinahanap ang customer gamit ang Contact Number O Order Number.
     * Kapag nakita, ibabalik ang buong impormasyon niya.
     * Kapag wala, ibabalik ang null.
     */
    public function find($keyword)
    {
        $keyword = trim($keyword);

        if ($keyword == '') {
            return null;
        }

        $sql = "SELECT customer_id, customer_name, contact_number, order_number
                FROM customers
                WHERE contact_number = :contact OR order_number = :orderNo
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':contact' => $keyword,
            ':orderNo' => $keyword
        ));

        $row = $stmt->fetch();

        if ($row == false) {
            return null;   // walang nakitang customer
        }
        return $row;
    }

    /**
     * Hanapin ang EKSAKTONG parehong tao.
     * Dapat magkatugma ang PANGALAN at ang CONTACT NUMBER.
     *
     * Bakit pareho? Kasi maaaring magkapamilya ang gumagamit ng iisang
     * numero. Kung contact number lang ang titingnan, mapupunta ang bill
     * sa maling tao.
     */
    public function findByNameAndContact($name, $contact)
    {
        $name    = trim($name);
        $contact = trim($contact);

        if ($name == '' || $contact == '') {
            return null;
        }

        $sql = "SELECT customer_id, customer_name, contact_number, order_number
                FROM customers
                WHERE LOWER(customer_name) = LOWER(:name)
                  AND contact_number = :contact
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':name'    => $name,
            ':contact' => $contact
        ));

        $row = $stmt->fetch();

        if ($row == false) {
            return null;
        }
        return $row;
    }

    /**
     * Hanapin ang customer gamit ang Order Number lamang.
     * Ginagamit para malaman kung may ibang gumamit na ng order number.
     */
    public function findByOrderNumber($orderNo)
    {
        $orderNo = trim($orderNo);

        if ($orderNo == '') {
            return null;
        }

        $sql = "SELECT customer_id, customer_name, contact_number, order_number
                FROM customers
                WHERE order_number = :orderNo
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':orderNo' => $orderNo));

        $row = $stmt->fetch();

        if ($row == false) {
            return null;
        }
        return $row;
    }

    /**
     * I-save ang customer at ibalik ang customer_id.
     *
     * Paano ito gumagana:
     *   1. Kung dati na siyang customer (parehong pangalan at contact),
     *      gagamitin ang lumang record - walang duplicate.
     *   2. Kung bagong tao, dapat hindi pa gamit ang Order Number.
     *   3. Kung malinis, isasave siya bilang bagong customer.
     */
    public function save($name, $contact, $orderNo)
    {
        $name    = trim($name);
        $contact = trim($contact);
        $orderNo = trim($orderNo);

        // 1) Dati na bang customer? Gamitin ang lumang record.
        $existing = $this->findByNameAndContact($name, $contact);
        if ($existing != null) {
            return (int) $existing['customer_id'];
        }

        // 2) Bagong tao - siguraduhing hindi pa gamit ang order number.
        $owner = $this->findByOrderNumber($orderNo);
        if ($owner != null) {
            throw new RuntimeException(
                'Order Number "' . $orderNo . '" is already used by '
                . $owner['customer_name'] . '. Please enter a different Order Number.'
            );
        }

        // 3) I-save ang bagong customer.
        $sql = "INSERT INTO customers (customer_name, contact_number, order_number)
                VALUES (:name, :contact, :orderNo)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':name'    => $name,
            ':contact' => $contact,
            ':orderNo' => $orderNo
        ));

        // Ibalik ang ID ng bagong naisave na customer.
        return (int) $this->db->lastInsertId();
    }
}
