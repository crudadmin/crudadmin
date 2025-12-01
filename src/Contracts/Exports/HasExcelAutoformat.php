<?php

namespace Admin\Contracts\Exports;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Str;

trait HasExcelAutoformat
{
    // public $autosizeExcept = [
    //     'Column Name A',
    //     'Column Name B',
    // ];

    protected function autoformatColumns($header)
    {
        $columns = [];

        foreach ($this->autoformatColumns ?? [] as $key => $format) {
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

                $this->setColumnsAutosize($sheet);
                $this->setColumnsAutoFilter($sheet);
            },
        ];
    }

    public function setColumnsAutosize($sheet)
    {
        // Apply autosize to all columns except whose columns
        $skipAutosizeColumns = collect($this->autosizeExcept ?? [])->map(function($key) {
            return $this->excelCharFromIndex($key);
        })->filter()->toArray();

        // Set autosize for given columns
        collect(range('A', $sheet->getHighestColumn()))->diff($skipAutosizeColumns)->each(function($char) use ($sheet) {
            $sheet->getColumnDimension($char)->setAutoSize(true);
        });
    }

    /**
     * Turns on auto filter for given columns
     *
     * @param  mixed $sheet
     * @param  mixed $columns
     * @return void
     */
    public function setColumnsAutoFilter($sheet)
    {
        $sheet->setAutoFilter('A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow());
    }

    /**
     * Returns excel column character from heading key name
     *
     * @param  mixed $index
     * @param  mixed $header
     * @return void
     */
    public function excelCharFromIndex($index, $header = null)
    {
        if ( is_string($index) ) {
            $header = $header ?? $this->headings();

            foreach ( $header as $key => $value ) {
                if ( Str::slug($value) == Str::slug($index) ) {
                    return Coordinate::stringFromColumnIndex($key+1);
                }
            }

            return;
        }

        return Coordinate::stringFromColumnIndex($index+1);
    }
}