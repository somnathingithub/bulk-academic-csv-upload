<?php
ini_set('max_execution_time', 3600); // Set maximum execution time to 300 seconds (5 minutes)
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
                        $collection_id = $this->checkEmptyData($br_id) ? $this->insertFreeCollectionTypeData($br_id, $this->getFreeCollectionTypeNameData()) : NULL;

                        $free_type_id = $this->checkEmptyData($collection_id) && $this->checkEmptyData($row[16], "Fee Head") ? $this->insertFeeTypeData($fee_category_id, $row[16], $collection_id, $br_id, $this->getModuleId($row[16])) : NULL;

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

        $output['execution_time'] = $this->formatExecutionTime($executionTime);

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

    private function insertFreeCollectionTypeData($br_id, $freeCollectionTableData)
    {
        // Transform our data array to work with IN clause and PDO
        $collectionHeads = array_column($freeCollectionTableData, 'collectionhead');
        $placeholders = str_repeat('?,', count($collectionHeads) - 1) . '?';

        // Prepare the statement for selecting existing entries
        $stmt = $this->db->prepare("SELECT collectionhead FROM freecollectiontype WHERE br_id = ? AND collectionhead IN ($placeholders)");
        $stmt->execute(array_merge([$br_id], $collectionHeads));
        $existingEntries = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Filter the data to include only the new entries
        $newEntries = array_filter($freeCollectionTableData, function ($entry) use ($existingEntries) {
            return !in_array($entry['collectionhead'], $existingEntries);
        });

        // Check if we have any new entries
        if (!empty($newEntries)) {
            // Create a batch insert query
            $query = "INSERT INTO freecollectiontype (br_id, collectionhead, collectiondesc) VALUES ";
            $values = [];
            foreach ($newEntries as $entry) {
                $query .= "(?, ?, ?),";
                array_push($values, $br_id, $entry['collectionhead'], $entry['collectiondesc']);
            }

            // Remove the trailing comma
            $query = substr($query, 0, -1);

            // Prepare and execute the statement
            $stmt = $this->db->prepare($query);
            $stmt->execute($values);

            return $this->db->lastInsertId();
        }
    }

    public function getFreeCollectionTypeNameData()
    {
        return [['collectionhead' => 'Academic', 'collectiondesc' => 'Description for Academic'], ['collectionhead' => 'Academic Misc', 'collectiondesc' => 'Description for Academic Misc'], ['collectionhead' => 'Hostel', 'collectiondesc' => 'Description for Hostel'], ['collectionhead' => 'Hostel Misc', 'collectiondesc' => 'Description for Hostel Misc'], ['collectionhead' => 'Transport', 'collectiondesc' => 'Description for Transport'], ['collectionhead' => 'Transport Misc', 'collectiondesc' => 'Description for Transport Misc']];
    }

    public function getModuleId($free_head){
        switch (trim($free_head)) {
            case 'Fine Fee':
                return 2;
                break;
            case 'Hostel & Mess Fee':
                return 3;
                break;
            default:
                return 1;
                break;
        }

    }

    private function insertFeeTypeData($fee_category, $fname, $collection_id, $br_id, $freeHeadType)
    {
        // Prepare the statement for selecting existing entries
        $stmt = $this->db->prepare("SELECT id FROM fee_types WHERE fee_category = :fee_category AND fname = :fname AND collection_id = :collection_id AND br_id = :br_id AND fee_type_ledger = :fee_type_ledger AND freeHeadType = :freeHeadType");
        $stmt->execute([':fee_category' => $fee_category, ':fname' => $fname, ':collection_id' => $collection_id, ':br_id' => $br_id, ':fee_type_ledger' => $fname, ':freeHeadType' => $freeHeadType]);

        // Fetch the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            // If we found a row, return its id
            return $result['id'];
        }

        // If we didn't find a row, insert a new one
        $stmt = $this->db->prepare("
    INSERT INTO fee_types (fee_category, fname, collection_id, br_id, fee_type_ledger, freeHeadType)
    VALUES (:fee_category, :fname, :collection_id, :br_id, :fee_type_ledger, :freeHeadType)
");
        $stmt->execute([':fee_category' => $fee_category, ':fname' => $fname, ':collection_id' => $collection_id, ':br_id' => $br_id, ':fee_type_ledger' => $fname, ':freeHeadType' => $freeHeadType]);

        // Return the id of the row we just inserted
        return $this->db->lastInsertId();
    }

    public function formatExecutionTime($executionTimeInSeconds)
    {
        $minutes = floor($executionTimeInSeconds / 60);
        $seconds = $executionTimeInSeconds % 60;
        return "{$minutes} minutes and {$seconds} seconds";
    }

    private function insertFinancialTransData($module_id, $adm_no, $amount, $crdr, $tran_date, $acad_year, $entry_mode, $voucher_no, $br_id, $type_of_consession = NULL)
    {
        // Check if type_of_consession is empty and set it to NULL
        if(empty($type_of_consession)){
            $type_of_consession = NULL;
        }

        // Prepare the statement for selecting existing entries
        $stmt = $this->db->prepare("SELECT id FROM financial_trans WHERE module_id = :module_id AND adm_no = :adm_no AND amount = :amount AND crdr = :crdr AND tran_date = :tran_date AND acad_year = :acad_year AND entry_mode = :entry_mode AND voucher_no = :voucher_no AND br_id = :br_id AND type_of_consession = :type_of_consession");
        $stmt->execute([':module_id' => $module_id, ':adm_no' => $adm_no, ':amount' => $amount, ':crdr' => $crdr, ':tran_date' => $tran_date, ':acad_year' => $acad_year, ':entry_mode' => $entry_mode, ':voucher_no' => $voucher_no, ':br_id' => $br_id, ':type_of_consession' => $type_of_consession]);

        // Fetch the result
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            // If we found a row, return its id
            return $result['id'];
        }

        // If we didn't find a row, insert a new one
        $stmt = $this->db->prepare("
    INSERT INTO financial_trans (module_id, adm_no, amount, crdr, tran_date, acad_year, entry_mode, voucher_no, br_id, type_of_consession)
    VALUES (:module_id, :adm_no, :amount, :crdr, :tran_date, :acad_year, :entry_mode, :voucher_no, :br_id, :type_of_consession)
");
        $stmt->execute([':module_id' => $module_id, ':adm_no' => $adm_no, ':amount' => $amount, ':crdr' => $crdr, ':tran_date' => $tran_date, ':acad_year' => $acad_year, ':entry_mode' => $entry_mode, ':voucher_no' => $voucher_no, ':br_id' => $br_id, ':type_of_consession' => $type_of_consession]);

        // Return the id of the row we just inserted
        return $this->db->lastInsertId();
    }


}
