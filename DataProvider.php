<?php

class DataProvider
{
    /**
     * Returns predefined collection type names.
     *
     * @return array
     */
    public function getFreeCollectionTypeNameData()
    {
        return [['collectionhead' => 'Academic', 'collectiondesc' => 'Description for Academic'], ['collectionhead' => 'Academic Misc', 'collectiondesc' => 'Description for Academic Misc'], ['collectionhead' => 'Hostel', 'collectiondesc' => 'Description for Hostel'], ['collectionhead' => 'Hostel Misc', 'collectiondesc' => 'Description for Hostel Misc'], ['collectionhead' => 'Transport', 'collectiondesc' => 'Description for Transport'], ['collectionhead' => 'Transport Misc', 'collectiondesc' => 'Description for Transport Misc']];
    }

    /**
     * Returns predefined module IDs based on fee head.
     *
     * @param string $free_head
     * @return int|null
     */
    public function getModuleId($free_head)
    {
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

    public function getDataFromEntryMode($row)
    {
        $crdr = NULL;
        $entryModeNo = NULL;
        $concessionNo = NULL;

        $entryModeArray = [
            17 => 0,
            19 => 15,
            20 => 15,
            21 => 16,
            22 => 12,
            23 => 14,
            24 => 1,
            25 => 1,
        ];

        $concession = [19 => 1, 20 => 2];

        foreach (array_merge([17], range(19, 25)) as $column_no) {
            $data = trim($row[$column_no]);
            if (in_array($data, [
                'Due Amount',
                'Concession Amount',
                'Scholarship Amount',
                'Reverse Concession Amount',
                'Write Off Amount',
                'Adjusted Amount',
                'Refund Amount',
                'Fund TranCfer Amount'
            ])) {
                continue;
            }
            if (in_array($column_no, [17, 24, 21]) && $data != 0) {
                $crdr = 'D';
                $entryModeNo = $entryModeArray[$column_no];
                $concessionNo = isset($concession[$column_no]) ? $concession[$column_no] : NULL;
                break;
            }
            if (in_array($column_no, [19, 20, 22, 23]) && $data != 0) {
                $crdr = 'C';
                $entryModeNo = $entryModeArray[$column_no];
                $concessionNo = isset($concession[$column_no]) ? $concession[$column_no] : NULL;
                break;
            }
            if ($column_no === 25 && $data != 0) {
                $crdr = 'positive and negative';
                $entryModeNo = $entryModeArray[$column_no];
                break;
            }
        }
        return ['crdr' => $crdr, 'entry_mode_no' => $entryModeNo, 'concession_no' => $concessionNo];
    }

    // Add other static data provider methods here as needed
}