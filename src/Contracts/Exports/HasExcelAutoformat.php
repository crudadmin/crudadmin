<?php

namespace Admin\Contracts\Exports;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

trait HasExcelAutoformat
{
    protected function autoformatColumns($header)
    {
        $autoColumnFormat = [
            // 'SSCC' => '@',
            // 'B' => '@',
            // 'EAN CARTON' => NumberFormat::FORMAT_NUMBER,
            // 'EAN SKU' => NumberFormat::FORMAT_NUMBER,

            // 'QUANTITY IN CARTON' => NumberFormat::FORMAT_NUMBER,
            // 'TOTAL QUANTITY' => NumberFormat::FORMAT_NUMBER,
        ];

        $columns = [];

        foreach ($autoColumnFormat as $key => $format) {
            if ( $index = $this->excelCharFromIndex($key, $header) ){
                $columns[$index] = $format;
            }
        }

        return $columns;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true], // Applies bold font to the first row (headings)
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'C0C0C0'], // Silver background
                ],
            ],
        ];
    }

    public function columnFormats(): array
    {
        return $this->autoformatColumns(
            $this->headings()
        );
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $header = $this->headings();

                $lastLetter = $this->excelCharFromIndex(count($header) - 1);

                // Apply autosize to all columns except whose columns
                $skipAutosizeColumns = [];
                foreach ($this->autosizeExcept ?? [] as $key) {
                    if ( $char = $this->excelCharFromIndex($key, $header) ){
                        $skipAutosizeColumns[] = $char;
                    }
                }

                // Set autosize for given columns
                $columns = array_diff(range('A', $lastLetter), $skipAutosizeColumns);
                foreach ($columns as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Set autofilter
                $sheet->setAutoFilter('A1:'.$lastLetter . $sheet->getHighestRow());
            },
        ];
    }


    /**
     * Returns character according to number index
     * Or returns character according to header index
     *
     * @param  mixed $index
     * @param  mixed $header
     * @return void
     */
    public function excelCharFromIndex($index, $header = [])
    {
        $letters = range('A', 'Z');

        if ( is_string($index) ) {
            foreach ( $header as $key => $value ) {
                if ( str_slug($value) == str_slug($index) ) {
                    return $letters[$key];
                }
            }

            return;
        }

        return $letters[$index];
    }
}