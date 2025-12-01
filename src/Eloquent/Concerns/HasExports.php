<?php

namespace Admin\Eloquent\Concerns;

use Admin\Contracts\Exports\AdminExport;
use Admin\Helpers\AdminRows;

trait HasExports
{
    public function getAdminExport($exportKey)
    {
        $exports = array_merge(
            $this->getProperty('buttons') ?: [],
            $this->getProperty('exports') ?: []
        );

        return collect($exports)->filter(function($classname){
            return is_subclass_of($classname, AdminExport::class, true);
        })->map(function($classname){
            $export = new $classname($this);

            return [
                'key' => AdminRows::getButtonKey($export),
                'class' => $export,
            ];
        })->firstWhere('key', $exportKey)['class'] ?? null;
    }
}
