<?php

namespace Admin\Eloquent\Concerns;

use Illuminate\Support\Str;

trait ModelIcons
{
    private function getIconList()
    {
        return [
            'article' => 'fa-file-text',
            'blog' => 'fa-file-text',
            'gallery' => 'fa-picture-o',
            'photo' => 'fa-picture-o',
            'form' => 'fa-wpforms',
            'user' => 'fa-users',
            'news' => 'fa-newspaper-o',
            'bulletin' => 'fa-newspaper-o',
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
            'store' => 'fa-shopping-cart',
            'order' => 'fa-credit-card',
            'payment' => 'fa-credit-card',
            'song' => 'fa-music',
            'region' => 'fa-map-marker',
            'city' => 'fa-map-marker',
            'statistic' => 'fa-pie-chart',
            'analysis' => 'fa-pie-chart',
            'delivery' => 'fa-truck',
            'category' => 'fa-bars',
            'department' => 'fa-bars',
            'about' => 'fa-info',
            'student' => 'fa-users',
            'team' => 'fa-users',
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
            'curriculum' => 'fa-book',
            'download' => 'fa-download',
            'work' => 'fa-briefcase',
            'faq' => 'fa-question-circle-o',
            'vote' => 'fa-question-circle-o',
            'pool' => 'fa-question-circle-o',
            'subject' => 'fa-tasks',
            'import' => 'fa-upload',
            'invoice' => 'fa-file-text-o',
            'proform' => 'fa-file-text-o',
            'export' => 'fa-download',
            'manufacturer' => 'fa-building-o',
            'contractor' => 'fa-handshake-o',
            'pricing' => 'fa-sliders',
            'country' => 'fa-flag',
            'review' => 'fa-commenting-o',
            'office' => 'fa-building',
            'param' => 'fa-gear',
            'report' => 'fa-table',
            'stopwatch' => 'fa-clock-o',
            'watch' => 'fa-clock-o',
            'contract' => 'fa-file-text-o',
            'place' => 'fa-map-marker',
            'company' => 'fa-building-o',
        ];
    }

    /*
     * Automatically returns model icon
     */
    public function getModelIcon()
    {
        if ($this->icon) {
            return $this->icon;
        }

        $icons = $this->getIconList();

        //If is not disabled automatic icons
        if (config('admin.icons', true) !== false) {
            $name = Str::singular(last(explode('_', $this->getTable())));

            if (array_key_exists($name, $icons)) {
                return $icons[$name];
            }
        }

        return 'fa-link';
    }
}
