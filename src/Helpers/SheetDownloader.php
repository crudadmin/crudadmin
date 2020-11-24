<?php

namespace Admin\Helpers;

use Admin\Helpers\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SheetDownloader
{
    private $model;
    private $rows;
    private $randKey;

    public function __construct($model, $rows)
    {
        $this->model = $model;
        $this->rows = $rows;
        $this->randKey = str_random(6);
    }

    private function getColumn($i)
    {
        $columns = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
        ];

        return $columns[$i];
    }

    public function getFileName()
    {
        return 'sheet-'.date('d.m.Y_H-i').'-'.$this->randKey.'.xlsx';
    }

    public static function getFilePath()
    {
        $directory = storage_path('app/xlsx');

        File::makeDirs($directory);

        return $directory;
    }

    public function generate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if (count($this->rows) == 0){
            return;
        }

        $columns = array_keys($this->rows[0]);

        //Create header
        foreach ($columns as $i => $key) {
            $field = $this->model->getField($key);
            $name = @$field['name'] ?: $key;

            $column = $this->getColumn($i);

            $sheet->setCellValue($column.'1', $name);
        }

        foreach ($this->rows as $i => $row) {
            foreach ($columns as $columnIndex => $column) {
                $value = @$row[$column];

                if ( $this->model->isFieldType($column, 'select') ){
                    $value = $this->model->getSelectOption($column, $value);
                }

                $column = $this->getColumn($columnIndex);

                $sheet->setCellValue($column.''.($i+2), $value);
            }
        }

        $path = self::getFilePath().'/'.$this->getFileName();

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }
}