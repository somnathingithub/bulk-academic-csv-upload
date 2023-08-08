<?php

class Helper
{
    /**
     * @param $rowData
     * @param $column_name
     * @return bool
     */
    public function checkEmptyData($rowData, $column_name = NULL)
    {
        if ($column_name !== NULL) {
            return (!empty($rowData) && $rowData !== $column_name);
        }
        return !empty($rowData);
    }

    /**
     * @param $executionTimeInSeconds
     * @return string
     */
    public function formatExecutionTime($executionTimeInSeconds)
    {
        $minutes = floor($executionTimeInSeconds / 60);
        $seconds = $executionTimeInSeconds % 60;
        return "{$minutes} minutes and {$seconds} seconds";
    }
}