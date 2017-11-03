<?php

namespace App\Services;

class FileService{

    /**
     * Generates a csv file from a hashmap of unique column keys mapped to column names. Each row is a hashmap mapping
     * the column keys to the row value for that column.
     * @param $colMap - A hashmap of id => name. The name is what ends up in the column header.
     * @param $rowMaps - An array of hashmaps for each row. Hashmap keys should correspond to the $colMap keys.
     * @param $filePath - The path to the csv file.
     */
    public static function writeCsv($colMap, $rowMaps, $filePath){

        // TODO: make sure that the columns line up correctly
        $emptyValue = '';

        $headerIds = array();
        $headerNames = array();
        foreach ($colMap as $id => $name){
            array_push($headerIds, $id);
            array_push($headerNames, $name);
        }


        $file = fopen($filePath, 'w');

        // Write headers
        fputcsv($file, $headerNames);
        foreach ($rowMaps as $rowMap){
            $row = array();
            foreach ($headerIds as $id){
                if(array_key_exists($id, $rowMap) && $rowMap[$id] != ''){
                    array_push($row, $rowMap[$id]);
                } else {
                    // Value doesn't exist or the string is empty
                    array_push($row, $emptyValue);
                }
            }
            // Write row
            fputcsv($file, $row);
        }


        fclose($file);

    }

}