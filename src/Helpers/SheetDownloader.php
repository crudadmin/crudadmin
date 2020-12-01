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

    private function getBelongsToValue($column, $value)
    {
        $field = $this->model->getField($column);
        $options = @$field['options'];

        $belongsTo = @$field['belongsTo']?:$field['belongsToMany'];
        $belongsTo = str_replace(',', '.', $belongsTo);
        $belongsTo = explode('.', $belongsTo);
        $belongsTo = array_slice($belongsTo, 1);
        $belongsTo = implode('.', $belongsTo);

        $ids = array_wrap($value);
        $array = [];

        foreach ($ids as $id) {
            $option = @$options[$id];

            //If select option does not exists
            if ( !$option ){
                continue;
            }

            if ( array_key_exists($belongsTo, $option) ){
                $array[] = $option[$belongsTo];
            } else {
                $string = $belongsTo;

                foreach ($option as $key => $v) {
                    $string = str_replace(':'.$key, $v, $string);
                }

                if ( $string !== $belongsTo ){
                    $array[] = $string;
                }
            }
        }

        return implode(', ', $array);
    }

    private function getColumnName($key, $field, $settings)
    {
        if ( $key == 'created_at' ){
            return _('Vytvorené dňa');
        } else if ( $key == 'updated_at' ){
            return _('Upravené dňa');
        }

        return @$field['name'] ?: @$settings['columns'][$key]['name'] ?: $key;
    }

    public function generate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if (count($this->rows) == 0){
            return;
        }

        $columns = array_keys($this->rows[0]);

        //Allow load all options
        $this->model->withAllOptions();

        $settings = $this->model->getModelSettings();

        //Create header
        foreach ($columns as $i => $key) {
            $field = $this->model->getField($key);
            $name = $this->getColumnName($key, $field, $settings);

            $column = $this->getColumn($i);

            $sheet->setCellValue($column.'1', $name);
        }

        foreach ($this->rows as $i => $row) {
            foreach ($columns as $columnIndex => $column) {
                $value = @$row[$column];

                if ( $this->model->hasFieldParam($column, ['belongsTo', 'belongsToMany']) ){
                    $value = $this->getBelongsToValue($column, $value);
                } else if ( $this->model->isFieldType($column, 'select') ){
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