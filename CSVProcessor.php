<?php
ini_set('max_execution_time', 3600); // Set maximum execution time to 300 seconds (5 minutes)
require_once 'database.php';
require_once 'DataProvider.php';
require_once 'DatabaseInserter.php';
require_once 'Helper.php';


class CSVProcessor
{
    private $db;
    private $dataProvider;
    private $databaseInserter;
    private $helper;

    /**
     * @param $db
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->dataProvider = new DataProvider();
        $this->helper = new Helper();
        $this->databaseInserter = new DatabaseInserter($this->db);
    }

    /**
     * @param $filename
     * @return array|false
     */
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
                        $branch_name = $row[11];
                        $free_category_name = $row[10];
                        $free_head_name = $row[16];
                        $adm_no = $row[8];
                        $tran_date = $row[1];
                        $voucher_no = $row[6];
                        $amount = $row[18];

                        $hasBranchName = $this->helper->checkEmptyData($row[11], "Faculty");
                        $hasFreeCategory = $this->helper->checkEmptyData($free_category_name, "Fee Category");
                        $hasFreeHead = $this->helper->checkEmptyData($free_head_name, "Fee Head");
                        $hasAdmNo = $this->helper->checkEmptyData($adm_no, "Admno/UniqueId");
                        $hasTransDate = $this->helper->checkEmptyData($tran_date, "Date");
                        $hasVoucherNo = $this->helper->checkEmptyData($voucher_no, "Voucher No.");
                        $hasAmount = $this->helper->checkEmptyData($amount, "Paid Amount");

                        $br_id = $hasBranchName ? $this->databaseInserter->insertFacultyData($branch_name) : NULL;
                        $hasBranchId = $this->helper->checkEmptyData($br_id);

                        $fee_category_id = $hasBranchId && $hasFreeCategory ? $this->databaseInserter->insertFeeCategoryData($br_id, $free_category_name) : NULL;

                        $collection_id = $hasBranchId ? $this->databaseInserter->insertFreeCollectionTypeData($br_id, $this->dataProvider->getFreeCollectionTypeNameData()) : NULL;

                        $hasCollectionId = $this->helper->checkEmptyData($collection_id);

                        $module_id = $this->dataProvider->getModuleId($free_head_name);

                        $free_type_id = $hasCollectionId && $hasFreeHead ? $this->databaseInserter->insertFeeTypeData($fee_category_id, $free_head_name, $collection_id, $br_id, $module_id) : NULL;

                        $entry_mode_data = $this->dataProvider->getDataFromEntryMode($row);

                        $financialTransData = [
                            'module_id' => $module_id,
                            'adm_no' => $hasAdmNo ? $adm_no : '',
                            'crdr' => $entry_mode_data['crdr'],
                            'tran_date' => $tran_date,
                            'acad_year' => $hasTransDate ? $tran_date : NULL,
                            'entry_mode' => $entry_mode_data['entry_mode_no'],
                            'voucher_no' => $hasVoucherNo ? $voucher_no : NULL,
                            'br_id' => $br_id,
                            'type_of_consession' => $entry_mode_data['concession_no'],
                        ];
                        $financialTranDetailsData = [[
                            'adm_no' => $hasAdmNo ? $adm_no : '',
                            'module_id' => $module_id,
                            'amount' => $hasAmount ? $amount : 0,
                            'head_id' => $free_type_id,
                            'crdr' => $entry_mode_data['crdr'],
                            'br_id' => $br_id,
                            'head_name' => $free_head_name,
                        ]];

                        $this->databaseInserter->insertFinancialData($financialTransData, $financialTranDetailsData);

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

        $output['execution_time'] = $this->helper->formatExecutionTime($executionTime);

        return $output;
    }

}
