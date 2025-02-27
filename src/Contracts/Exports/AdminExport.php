<?php

namespace Admin\Contracts\Exports;

use Excel;
use Str;

class AdminExport
{
    public $name;

    public $icon;

    public $model;

    public $path;

    public $disk = 'crudadmin.uploads_private';

    public static $tempDir = 'temp_download_exports';

    public function __construct(){}

    public function getName()
    {
        return $this->name;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function getKey()
    {
        return class_basename($this);
    }

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function rows()
    {
        $query = $this->query($this->model);

        return $query->get();
    }

    public function filename()
    {
        return Str::snake(class_basename($this)).'-'.date('Y-m-d\_H-i').'_'.str_random(3);
    }

    public function save()
    {
        $this->filename = $this->filename().'.xlsx';
        $this->path = static::$tempDir.'/'.$this->filename;

        return Excel::store($this, $this->path, $this->disk);
    }
}