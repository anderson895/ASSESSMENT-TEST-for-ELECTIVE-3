<?php
/**
 * Employee.php
 * -----------------------------------------------------------------
 * Class para sa mga empleyado (cashier) ng MRA STORE.
 *
 * Ang napiling cashier ang lumalabas sa resibo:
 *   Cashier Name, Employee ID, Position, at Shift.
 * -----------------------------------------------------------------
 */
class Employee
{
    private $db;   // koneksyon sa database

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Kunin ang lahat ng aktibong empleyado.
     * Ito ang ginagamit ng "Cashier on Duty" na dropdown sa webpage.
     */
    public function allActive()
    {
        $sql = "SELECT employee_id, employee_code, employee_name, position, shift
                  FROM employees
                 WHERE is_active = 1
              ORDER BY employee_code";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Hanapin ang isang empleyado gamit ang employee_id.
     * Kapag wala, ibabalik ang null.
     */
    public function find($employeeId)
    {
        $employeeId = (int) $employeeId;

        if ($employeeId <= 0) {
            return null;
        }

        $sql = "SELECT employee_id, employee_code, employee_name, position, shift
                  FROM employees
                 WHERE employee_id = :id
                 LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':id' => $employeeId));

        $row = $stmt->fetch();

        if ($row == false) {
            return null;
        }
        return $row;
    }
}
