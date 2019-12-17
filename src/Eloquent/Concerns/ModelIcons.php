<?php

namespace Admin\Eloquent\Concerns;

use Illuminate\Support\Str;

trait ModelIcons
{
    private function getIconList()
    {
        return [
            'article' => 'fa-file-alt',
            'blog' => 'fa-file-alt',
            'gallery' => 'fa-file-image',
            'photo' => 'fa-file-image',
            'form' => 'fa-wpforms',
            'submission' => 'fa-wpforms',
            'user' => 'fa-users',
            'news' => 'far fa-newspaper',
            'bulletin' => 'far fa-newspaper',
            'client' => 'fa-address-book',
            'partner' => 'fa-address-book',
            'contact' => 'fa-address-card',
            'language' => 'fa-globe',
            'calculator' => 'fa-calculator',
            'insurance' => 'fa-university',
            'car' => 'fa-car',
            'bus' => 'fa-bus',
            'motorcycle' => 'fa-motorcycle',
            'bicycle' => 'fa-bicycle',
            'term' => 'far fa-check-square',
            'vop' => 'far fa-check-square',
            'setting' => 'fa-cog',
            'hour' => 'far fa-clock',
            'product' => 'fa-shopping-basket',
            'basket' => 'fa-shopping-basket',
            'store' => 'fa-shopping-cart',
            'order' => 'fa-credit-card',
            'payment' => 'fa-credit-card',
            'song' => 'fa-music',
            'region' => 'fa-map-marker-alt',
            'city' => 'fa-map-marker-alt',
            'statistic' => 'fa-chart-pie',
            'analysis' => 'fa-chart-pie',
            'delivery' => 'fa-truck',
            'service' => 'fa-bars',
            'sector' => 'fa-bars',
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
            'pdf' => 'far fa-file-pdf',
            'certificate' => 'fa-certificate',
            'slider' => 'fa-th-large',
            'complaint' => 'fa-sync',
            'brand' => 'fa-tags',
            'curriculum' => 'fa-book',
            'download' => 'fa-download',
            'work' => 'fa-briefcase',
            'faq' => 'far fa-question-circle',
            'vote' => 'far fa-question-circle',
            'pool' => 'far fa-question-circle',
            'subject' => 'fa-tasks',
            'import' => 'fa-upload',
            'invoice' => 'far fa-file-alt',
            'proform' => 'far fa-file-alt',
            'export' => 'fa-download',
            'manufacturer' => 'far fa-building',
            'contractor' => 'far fa-handshake',
            'pricing' => 'fa-sliders-h',
            'country' => 'fa-flag',
            'review' => 'far fa-commenting',
            'office' => 'fa-building',
            'param' => 'fa-gear',
            'report' => 'fa-table',
            'stopwatch' => 'far fa-clock',
            'watch' => 'far fa-clock',
            'contract' => 'far fa-file-alt',
            'place' => 'fa-map-marker',
            'company' => 'far fa-building',
            'field' => 'fa-keyboard',
            'chart' => 'fa-chart-line',
            'risk' => 'fa-chart-line',
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
