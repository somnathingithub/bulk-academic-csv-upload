<?php
ini_set('max_execution_time', 300); // Set maximum execution time to 300 seconds (5 minutes)
require_once 'database.php';

class CSVProcessor
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function process($filename)
    {
        $startTime = microtime(true);  // get start time

        if (!file_exists($filename) || !is_readable($filename)) {
            return false;
        }

        $output = [];

        if (($handle = fopen($filename, 'r')) !== false) {
            $this->db->beginTransaction();

            try {
                while (($row = fgetcsv($handle)) !== false) {

                    try {
                        $br_id = $this->checkEmptyData($row[11], "Faculty") ? $this->insertFacultyData($row[11]) : NULL;
                        $fee_category_id = $this->checkEmptyData($br_id) && $this->checkEmptyData($row[10], "Fee Category") ? $this->insertFeeCategoryData($br_id, $row[10]) : NULL;

                    } catch (Exception $e) {
                        if ($this->db->inTransaction()) {
                            $this->db->rollback();
                        }

                        $output['status'] = false;
                        $output['message'] = "There was an error importing the CSV: " . $e->getMessage();
                        break;  // If there's an error, break out of the loop
                    }
                }

                if ($this->db->inTransaction()) {
                    $this->db->commit();
                }

                fclose($handle);

                if (!isset($output['status']) || $output['status'] !== false) {
                    $output['status'] = true;
                    $output['message'] = "CSV imported successfully.";
                }
            } catch (Exception $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollback();
                }

                $output['status'] = false;
                $output['message'] = "There was an error importing the CSV: " . $e->getMessage();
            }

        }

        $endTime = microtime(true); // get end time
        $executionTime = ($endTime - $startTime); // calculate execution time

        $output['execution_time'] = $executionTime;

        return $output;
    }


    public function checkEmptyData($rowData, $column_name = NULL)
    {
        if ($column_name !== NULL) {
            return (!empty($rowData) && $rowData !== $column_name);
        }
        return !empty($rowData);
    }

    private function insertFacultyData($br_name)
    {
        // First, try to find an existing row with the same br_name
        $stmt = $this->db->prepare("SELECT br_id FROM branches WHERE br_name = :br_name");
        $stmt->execute([':br_name' => $br_name]);

        // Fetch the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            // If we found a row, return its br_id
            return $result['br_id'];
        }

        // If we didn't find a row, insert a new one
        $stmt = $this->db->prepare("INSERT INTO branches (br_name) VALUES (:br_name)");
        $stmt->execute([':br_name' => $br_name]);

        // Return the br_id of the row we just inserted
        return $this->db->lastInsertId();
    }


    private function insertFeeCategoryData($br_id, $freecategory)
    {
        // First, try to find an existing row with the same br_id and freecategory
        $stmt = $this->db->prepare("SELECT id FROM fee_category WHERE br_id = :br_id AND freecategory = :freecategory");
        $stmt->execute([':br_id' => $br_id, ':freecategory' => $freecategory]);

        // Fetch the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            // If we found a row, return its fee_category_id
            return $result['id'];
        }

        // If we didn't find a row, insert a new one
        $stmt = $this->db->prepare("
        INSERT INTO fee_category (br_id, freecategory)
        VALUES (:br_id, :freecategory)
    ");
        $stmt->execute([':br_id' => $br_id, ':freecategory' => $freecategory]);

        // Return the fee_category_id of the row we just inserted
        return $this->db->lastInsertId();
    }


}
