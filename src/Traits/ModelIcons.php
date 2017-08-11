<?php

namespace Gogol\Admin\Traits;

use Illuminate\Support\Str;

trait ModelIcons
{
    private function getIconList()
    {
        return [
            'article' => 'fa-file-text',
            'gallery' => 'fa-picture-o',
            'photo' => 'fa-picture-o',
            'form' => 'fa-wpforms',
            'user' => 'fa-users',
            'news' => 'fa-newspaper-o',
            'client' => 'fa-address-book-o',
            'partner' => 'fa-address-book-o',
            'contact' => 'fa-address-card-o',
            'language' => 'fa-globe',
            'calculator' => 'fa-calculator',
            'insurance' => 'fa-university',
            'car' => 'fa-car',
            'bus' => 'fa-bus',
            'motorcycle' => 'fa-motorcycle',
            'bicycle' => 'fa-bicycle',
            'term' => 'fa-check-square-o',
            'vop' => 'fa-check-square-o',
            'setting' => 'fa-cog',
            'hour' => 'fa-clock-o',
            'product' => 'fa-shopping-basket',
            'basket' => 'fa-shopping-basket',
            'order' => 'fa-credit-card',
            'payment' => 'fa-credit-card',
            'song' => 'fa-music',
            'region' => 'fa-map-marker',
            'city' => 'fa-map-marker',
            'statistic' => 'fa-pie-chart',
            'analysis' => 'fa-pie-chart',
            'delivery' => 'fa-truck',
            'category' => 'fa-bars',
            'about' => 'fa-info',
            'student' => 'fa-graduation-cap',
            'grade' => 'fa-graduation-cap',
            'college' => 'fa-graduation-cap',
            'faculty' => 'fa-graduation-cap',
            'application' => 'fa-wpforms',
            'social' => 'fa-share-alt',
            'pdf' => 'fa-file-pdf-o',
            'certificate' => 'fa-certificate',
            'slider' => 'fa-th-large',
            'complaint' => 'fa-refresh',
            'brand' => 'fa-tags',
        ];
    }


    /*
     * Automatically returns model icon
     */
    public function getModelIcon()
    {
        if ( $this->icon )
            return $this->icon;

        $icons = $this->getIconList();

        //If is not disabled automatic icons
        if ( config('admin.icons', true) !== false )
        {
            $name = Str::singular(last(explode('_', $this->getTable())));

            if ( array_key_exists($name, $icons) )
                return $icons[$name];
        }

        return 'fa-link';
    }
}