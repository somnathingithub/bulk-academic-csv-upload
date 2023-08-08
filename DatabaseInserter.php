<?php

class DatabaseInserter
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * @param $br_name
     * @return mixed
     */
    public function insertFacultyData($br_name)
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

    /**
     * @param $br_id
     * @param $freecategory
     * @return mixed
     */
    public function insertFeeCategoryData($br_id, $freecategory)
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

    /**
     * @param $br_id
     * @param $freeCollectionTableData
     * @return void
     */
    public function insertFreeCollectionTypeData($br_id, $freeCollectionTableData)
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

        return null;
    }

    /**
     * @param $fee_category
     * @param $fname
     * @param $collection_id
     * @param $br_id
     * @param $freeHeadType
     * @return mixed
     */
    public function insertFeeTypeData($fee_category, $fname, $collection_id, $br_id, $freeHeadType)
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

    /**
     * @param $module_id
     * @param $adm_no
     * @param $amount
     * @param $crdr
     * @param $tran_date
     * @param $acad_year
     * @param $entry_mode
     * @param $voucher_no
     * @param $br_id
     * @param $type_of_consession
     * @return mixed
     */
    public function insertFinancialTransData($module_id, $adm_no, $amount, $crdr, $tran_date, $acad_year, $entry_mode, $voucher_no, $br_id, $type_of_consession = NULL)
    {
        // Check if type_of_consession is empty and set it to NULL
        if (empty($type_of_consession)) {
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

    public function insertFinancialData($financialTransData, $financialTranDetailsData)
    {
        // Step 1: Insert into Parent Table (financial_trans)
        $stmt = $this->db->prepare("INSERT INTO financial_trans (module_id, adm_no, crdr, tran_date, acad_year, entry_mode, voucher_no, br_id, type_of_consession) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $financialTransData['module_id'],
            $financialTransData['adm_no'],
            $financialTransData['crdr'],
            $financialTransData['tran_date'],
            $financialTransData['acad_year'],
            $financialTransData['entry_mode'],
            $financialTransData['voucher_no'],
            $financialTransData['br_id'],
            $financialTransData['type_of_consession']
        ]);

        // Capture the last insert ID
        $lastInsertId = $this->db->lastInsertId();

        // Step 2: Insert into Child Table (financial_tran_details)
        $stmt = $this->db->prepare("INSERT INTO financial_tran_details (financial_trans_id, adm_no, module_id, amount, head_id, crdr, br_id, head_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($financialTranDetailsData as $detail) {
            $stmt->execute([
                $lastInsertId,
                $detail['adm_no'],
                $detail['module_id'],
                $detail['amount'],
                $detail['head_id'],
                $detail['crdr'],
                $detail['br_id'],
                $detail['head_name']
            ]);
        }

        // Step 3: Update Amount in Parent Table
        $stmt = $this->db->prepare("UPDATE financial_trans SET amount = (SELECT SUM(amount) FROM financial_tran_details WHERE adm_no = ?) WHERE adm_no = ?");
        $stmt->execute([$detail['adm_no'], $detail['adm_no']]);

        $this->db->commit();
        return $lastInsertId;
    }
}