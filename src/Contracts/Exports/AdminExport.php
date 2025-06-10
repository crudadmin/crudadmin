<?php

namespace Admin\Contracts\Exports;

use Str;
use Excel;
use Storage;
use Admin\Helpers\AdminRows;

class AdminExport
{
    public $name;

    public $icon;

    public $model;

    public $format = \Maatwebsite\Excel\Excel::XLSX;

    private $filename;

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
        $query = $this->model->newQuery();

        $query = $this->filter($query);

        $query = $this->query($query);

        return $query->get();
    }

    public function filter($query)
    {
        $query = (new AdminRows($this->model, request()))->getRowsDataQuery($query);

        return $query;
    }

    public function filename()
    {
        return Str::snake(class_basename($this)).'-'.date('Y-m-d\_H-i').'_'.str_random(3).'.'.$this->extension();
    }

    public function extension()
    {
        return strtolower($this->format);
    }

    public function disk()
    {
        return 'crudadmin.uploads_private';
    }

    public function path()
    {
        return static::$tempDir.'/'.($this->filename ?: $this->filename());
    }

    public function basepath()
    {
        return Storage::disk($this->disk())->path($this->path());
    }

    public function save()
    {
        // Cache filename
        $this->filename = $this->filename();

        return Excel::store(
            $this,
            $this->path($this->filename),
            $this->disk(),
            $this->format
        );
    }

    public function html()
    {
        return Excel::raw($this, \Maatwebsite\Excel\Excel::HTML);
    }
}