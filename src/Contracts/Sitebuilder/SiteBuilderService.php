<?php

namespace Admin\Contracts\Sitebuilder;

use Admin\Core\Contracts\DataStore;

class SiteBuilderService
{
    use DataStore;

    public static function getTypes()
    {
        return config('admin.sitebuilder_types', []);
    }

    public static function getClasses()
    {
        return (new static)->cache('classes', function(){
            return array_map(function($class){
                return new $class;
            }, self::getTypes());
        });
    }

    public static function getByType($type)
    {
        foreach (self::getClasses() as $class) {
            if ( $class->getPrefix() == $type ){
                return $class;
            }
        }
    }
}