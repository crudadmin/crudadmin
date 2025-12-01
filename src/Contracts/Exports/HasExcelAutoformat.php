<?php

namespace Admin\Contracts\Exports;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Str;

trait HasExcelAutoformat
{
    // public $autoformatColumns = [
    //     'SSCC' => '@',
    //     'B' => '@',
    //     'EAN' => NumberFormat::FORMAT_NUMBER,
    // ];

    // public $autosizeExcept = [
    //     'Column Name A',
    //     'Column Name B',
    // ];

    /**
     * Add styling to header row
     *
     * @param  mixed $sheet
     * @return void
     */
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

    /**
     * Formats columns according to $autoformatColumns
     *
     * @return array
     */
    public function columnFormats(): array
    {
        return $this->getAutoformatColumns(
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

    /**
     * Returns array of columns with their formats
     *
     * @param  mixed $header
     * @return void
     */
    protected function getAutoformatColumns($header)
    {
        return collect($this->autoformatColumns ?? [])->mapWithKeys(function($value, $key) use ($header) {
            return [$this->excelCharFromIndex($key, $header) => $value];
        })->toArray();
    }

    /**
     * Autosizes all columns except those in $autosizeExcept
     *
     * @param  mixed $sheet
     * @return void
     */
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