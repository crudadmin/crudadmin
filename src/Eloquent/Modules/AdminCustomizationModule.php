<?php

namespace Admin\Eloquent\Modules;

use Admin\Core\Eloquent\Concerns\AdminModelModule;
use Admin\Core\Eloquent\Concerns\AdminModelModuleSupport;
use Admin;

class AdminCustomizationModule extends AdminModelModule implements AdminModelModuleSupport
{
    public function isActive($model)
    {
        return Admin::isAdmin();
    }

    private function getModelData()
    {
        return [
            'article' => [
                'icon' => 'fa-file-alt',
                'settings.title.create' => _('Nový článok'),
                'settings.title.update' => _('Upravujete článok č. :id'),
                'settings.buttons.create' => _('Nový článok'),
            ],
            'blog' => [
                'icon' => 'fa-file-alt',
                'settings.title.create' => _('Nový článok'),
                'settings.title.update' => _('Upravujete článok č. :id'),
                'settings.buttons.create' => _('Nový článok'),
            ],
            'gallery' => [
                'icon' => 'fa-file-image'
            ],
            'photo' => [
                'icon' => 'fa-file-image'
            ],
            'form' => [
                'icon' => 'fa-wpforms'
            ],
            'submission' => [
                'icon' => 'fa-wpforms'
            ],
            'user' => [
                'icon' => 'fa-users'
            ],
            'news' => [
                'icon' => 'far fa-newspaper'
            ],
            'bulletin' => [
                'icon' => 'far fa-newspaper'
            ],
            'client' => [
                'icon' => 'fa-address-book'
            ],
            'partner' => [
                'icon' => 'fa-address-book'
            ],
            'contact' => [
                'icon' => 'fa-address-card'
            ],
            'language' => [
                'icon' => 'fa-globe'
            ],
            'calculator' => [
                'icon' => 'fa-calculator'
            ],
            'insurance' => [
                'icon' => 'fa-university'
            ],
            'car' => [
                'icon' => 'fa-car'
            ],
            'bus' => [
                'icon' => 'fa-bus'
            ],
            'motorcycle' => [
                'icon' => 'fa-motorcycle'
            ],
            'bicycle' => [
                'icon' => 'fa-bicycle'
            ],
            'term' => [
                'icon' => 'far fa-check-square'
            ],
            'vop' => [
                'icon' => 'far fa-check-square'
            ],
            'setting' => [
                'icon' => 'fa-cog'
            ],
            'hour' => [
                'icon' => 'far fa-clock'
            ],
            'product' => [
                'icon' => 'fa-shopping-basket'
            ],
            'basket' => [
                'icon' => 'fa-shopping-basket'
            ],
            'store' => [
                'icon' => 'fa-shopping-cart'
            ],
            'order' => [
                'icon' => 'fa-credit-card'
            ],
            'payment' => [
                'icon' => 'fa-credit-card'
            ],
            'song' => [
                'icon' => 'fa-music'
            ],
            'region' => [
                'icon' => 'fa-map-marker-alt'
            ],
            'city' => [
                'icon' => 'fa-map-marker-alt'
            ],
            'statistic' => [
                'icon' => 'fa-chart-pie'
            ],
            'analysis' => [
                'icon' => 'fa-chart-pie'
            ],
            'delivery' => [
                'icon' => 'fa-truck'
            ],
            'service' => [
                'icon' => 'fa-bars'
            ],
            'sector' => [
                'icon' => 'fa-bars'
            ],
            'category' => [
                'icon' => 'fa-bars'
            ],
            'department' => [
                'icon' => 'fa-bars'
            ],
            'about' => [
                'icon' => 'fa-info'
            ],
            'student' => [
                'icon' => 'fa-users'
            ],
            'team' => [
                'icon' => 'fa-users'
            ],
            'grade' => [
                'icon' => 'fa-graduation-cap'
            ],
            'college' => [
                'icon' => 'fa-graduation-cap'
            ],
            'faculty' => [
                'icon' => 'fa-graduation-cap'
            ],
            'application' => [
                'icon' => 'fa-wpforms'
            ],
            'social' => [
                'icon' => 'fa-share-alt'
            ],
            'pdf' => [
                'icon' => 'far fa-file-pdf'
            ],
            'certificate' => [
                'icon' => 'fa-certificate'
            ],
            'slider' => [
                'icon' => 'fa-th-large'
            ],
            'complaint' => [
                'icon' => 'fa-sync'
            ],
            'brand' => [
                'icon' => 'fa-tags'
            ],
            'curriculum' => [
                'icon' => 'fa-book'
            ],
            'download' => [
                'icon' => 'fa-download'
            ],
            'work' => [
                'icon' => 'fa-briefcase'
            ],
            'faq' => [
                'icon' => 'far fa-question-circle'
            ],
            'vote' => [
                'icon' => 'far fa-question-circle'
            ],
            'pool' => [
                'icon' => 'far fa-question-circle'
            ],
            'subject' => [
                'icon' => 'fa-tasks'
            ],
            'import' => [
                'icon' => 'fa-upload'
            ],
            'invoice' => [
                'icon' => 'far fa-file-alt'
            ],
            'proform' => [
                'icon' => 'far fa-file-alt'
            ],
            'export' => [
                'icon' => 'fa-download'
            ],
            'manufacturer' => [
                'icon' => 'far fa-building'
            ],
            'contractor' => [
                'icon' => 'far fa-handshake'
            ],
            'pricing' => [
                'icon' => 'fa-sliders-h'
            ],
            'country' => [
                'icon' => 'fa-flag'
            ],
            'review' => [
                'icon' => 'far fa-commenting'
            ],
            'office' => [
                'icon' => 'fa-building'
            ],
            'param' => [
                'icon' => 'fa-gear'
            ],
            'report' => [
                'icon' => 'fa-table'
            ],
            'stopwatch' => [
                'icon' => 'far fa-clock'
            ],
            'watch' => [
                'icon' => 'far fa-clock'
            ],
            'contract' => [
                'icon' => 'far fa-file-alt'
            ],
            'place' => [
                'icon' => 'fa-map-marker'
            ],
            'company' => [
                'icon' => 'far fa-building'
            ],
            'field' => [
                'icon' => 'fa-keyboard'
            ],
            'chart' => [
                'icon' => 'fa-chart-line'
            ],
            'risk' => [
                'icon' => 'fa-chart-line'
            ],
            'tax' => [
                'icon' => 'fa-hand-holding-usd'
            ],
        ];
    }

    public function getLastTableName()
    {
        return str_singular(last(explode('_', $this->getModel()->getTable())));
    }

    public function adminModelRender(&$response = [])
    {
        $data = $this->getModelData();

        $name = $this->getLastTableName($this);

        if (array_key_exists($name, $data)) {
            foreach ($data[$name] as $key => $value) {
                if ( array_get($response, $key) == null ) {
                    array_set($response, $key, $value);
                }
            }
        }

        return $response;
    }
}
