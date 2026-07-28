<?php
/**
 * Customer.php
 * -----------------------------------------------------------------
 * Class para sa customer. Dito ginagawa ang paghahanap (Find button)
 * at ang pag-save ng customer sa database.
 *
 * TANDAAN: Hindi na kailangang mag-type ng Order Number ang cashier.
 * AUTOMATIC nang ginagawa ang order number tuwing may na-save na bill
 * (tingnan ang Order::nextOrderNumber). Ang kailangan lang dito ay
 * ang PANGALAN at ang CONTACT NUMBER.
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
     *
     * Tatlo ang tinitingnan nito:
     *   1. contact_number ng customer
     *   2. order_number ng kahit alin sa mga order niya (ito ang
     *      nakalimbag sa resibo)
     *   3. lumang order_number na nakadikit sa customer record
     *      (mula pa sa unang bersyon ng sistema)
     *
     * Kapag wala, ibabalik ang null.
     */
    public function find($keyword)
    {
        $keyword = trim($keyword);

        if ($keyword == '') {
            return null;
        }

        $sql = "SELECT c.customer_id, c.customer_name, c.contact_number
                  FROM customers c
             LEFT JOIN orders o ON o.customer_id = c.customer_id
                 WHERE c.contact_number = :contact
                    OR c.order_number   = :legacyNo
                    OR o.order_number   = :orderNo
              ORDER BY c.customer_id
                 LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':contact'  => $keyword,
            ':legacyNo' => $keyword,
            ':orderNo'  => $keyword
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

        // Ang pangalan lang ang talagang kailangan. Kapag walang contact
        // number (walk-in), hahanapin natin ang dating walk-in na may
        // parehong pangalan — kaya hindi ito magiging duplicate.
        if ($name == '') {
            return null;
        }

        $sql = "SELECT customer_id, customer_name, contact_number
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
     * I-save ang customer at ibalik ang customer_id.
     *
     * Paano ito gumagana:
     *   1. Kung dati na siyang customer (parehong pangalan at contact),
     *      gagamitin ang lumang record - walang duplicate.
     *   2. Kung bagong tao, isasave siya bilang bagong customer.
     */
    public function save($name, $contact)
    {
        $name    = trim($name);
        $contact = trim($contact);

        // Ang PANGALAN lang ang kailangan. OPTIONAL na ang contact number
        // para mabilis ang walk-in na hindi nagbibigay ng numero.
        if ($name == '') {
            throw new RuntimeException(
                'Please enter the Customer Name.'
            );
        }

        // 1) Dati na bang customer? Gamitin ang lumang record.
        $existing = $this->findByNameAndContact($name, $contact);
        if ($existing != null) {
            return (int) $existing['customer_id'];
        }

        // 2) Bagong tao - i-save siya.
        $sql = "INSERT INTO customers (customer_name, contact_number)
                VALUES (:name, :contact)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(
            ':name'    => $name,
            ':contact' => $contact
        ));

        // Ibalik ang ID ng bagong naisave na customer.
        return (int) $this->db->lastInsertId();
    }
}
