<?php

namespace Admin\Contracts\Exports;

class AdminExport
{
    public $name;

    public $icon;

    public function __construct()
    {

    }

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
}