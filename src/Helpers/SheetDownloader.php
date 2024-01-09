<?php

namespace Admin\Helpers;

use Admin\Helpers\File;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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

        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '300');
    }

    private function getColumn($i)
    {
        $columns = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
            'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ',
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
        //If custom value has been returned
        if ( is_string($value) ){
            return $value;
        }

        $field = $this->model->getField($column);
        $options = $field['options'] ?? null;

        $belongsTo = $field['belongsTo'] ?? $field['belongsToMany'] ?? null;
        $belongsTo = str_replace(',', '.', $belongsTo);
        $belongsTo = explode('.', $belongsTo);
        $belongsTo = array_slice($belongsTo, 1);
        $belongsTo = implode('.', $belongsTo);

        $ids = array_wrap($value);

        $array = [];

        foreach ($ids as $id) {
            $option = $options[$id] ?? null;

            //If select option does not exists
            if ( !$option ){
                continue;
            }

            if ( array_key_exists($belongsTo, $option) ){
                $array[] = $option[$belongsTo];
            } else {
                $string = $belongsTo;

                foreach ($option as $key => $v) {
                    $v = array_values(array_wrap($v))[0] ?? '';

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

        return @$field['column_name'] ?: @$field['name'] ?: @$settings['columns'][$key]['name'] ?: $key;
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
            if ( substr($key, 0, 1) == '$' ){
                continue;
            }

            $field = $this->model->getField($key);
            $name = $this->getColumnName($key, $field, $settings);

            $column = $this->getColumn($i);

            $sheet->setCellValue($column.'1', $name)->getStyle($column.'1')->applyFromArray([ 'font' => [ 'bold' => true ] ]);;
        }

        foreach ($this->rows as $i => $row) {
            foreach ($columns as $columnIndex => $column) {
                if ( substr($column, 0, 1) == '$' ){
                    continue;
                }

                $value = $this->getFieldValue($column, $row);

                $column = $this->getColumn($columnIndex);

                $sheet->setCellValue($column.''.($i+2), $value);
            }
        }

        $path = self::getFilePath().'/'.$this->getFileName();

        if ( method_exists($this->model, 'setExcelSheet') ){
            $this->model->setExcelSheet($spreadsheet, $this->rows);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    private function getFieldValue($column, $row)
    {
        $rawValue = $row[$column] ?? null;

        $value = null;

        if ( $rawValue instanceof Collection ){
            $rawValue = $rawValue->toArray();
        }

        if ( $this->model->hasFieldParam($column, ['belongsTo']) ){
            $value = $this->getBelongsToValue($column, $rawValue);
        } else if ( $this->model->hasFieldParam($column, ['belongsToMany']) ){
            $value = $this->getBelongsToValue($column, $rawValue);
        } else if ( $this->model->isFieldType($column, 'select') ){
            $value = $this->model->getSelectOption($column, $rawValue);
        } else if ( $this->model->hasFieldParam($column, 'locale') || $column == 'slug' && $this->model->hasLocalizedSlug() ){
            $model = $this->model->forceFill([ $column => $rawValue]);

            if ( $column == 'slug' ){
                $value = $model->getSlug();
            } else {
                $value = strip_tags($model->{$column});
            }
        } else if ( in_array($column, ['created_at', 'updated_at', 'deleted_at']) ) {
            $value = $rawValue ? (new Carbon($rawValue))->format('d.m.Y H:i:s') : null;
        } else if ( is_bool($rawValue) ) {
            $value = $rawValue === true ? _('Áno') : _('Nie');
        } else {
            $value = $rawValue;
        }

        if ( method_exists($this->model, $methodMutatorName = 'setSheet'.Str::studly($column).'Attribute') ){
            return $this->model->{$methodMutatorName}($value, $row);
        }

        if ( is_string($value) || is_numeric($value) ) {
            return trim(strip_tags($value ?: ''));
        }
    }
}