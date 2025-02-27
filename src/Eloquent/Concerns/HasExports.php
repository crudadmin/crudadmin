<?php

namespace Admin\Eloquent\Concerns;

trait HasExports
{
    public function getAdminExport($exportKey)
    {
        return collect($this->getProperty('exports', []))->map(function($classname){
            $export = new $classname;
            $export->setModel($this);

            return [
                'key' => $export->getKey(),
                'class' => $export,
            ];
        })->firstWhere('key', $exportKey)['class'] ?? null;
    }
}
