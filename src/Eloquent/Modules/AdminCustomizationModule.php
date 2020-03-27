<?php

namespace Admin\Eloquent\Modules;

use Admin;
use AdminLocalization;
use Admin\Core\Eloquent\Concerns\AdminModelModule;
use Admin\Core\Eloquent\Concerns\AdminModelModuleSupport;
use Gettext\Translations;

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
                'settings.title.create' => $this->t(_('Nový článok')),
                'settings.title.update' => $this->t(_('Upravujete článok č. :id')),
                'settings.buttons.create' => $this->t(_('Nový článok')),
            ],
            'blog' => [
                'icon' => 'fa-file-alt',
                'settings.title.create' => $this->t(_('Nový článok')),
                'settings.title.update' => $this->t(_('Upravujete článok č. :id')),
                'settings.buttons.create' => $this->t(_('Nový článok')),
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
                'icon' => 'fa-users',
                'settings.title.create' => $this->t(_('Nový používateľ')),
                'settings.title.update' => $this->t(_('Upravujete používateľa č. :id')),
                'settings.buttons.create' => $this->t(_('Nový používateľ')),
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
            'time' => [
                'icon' => 'fa-clock-o',
                'settings' => [
                    'title' => [
                        'create' => $this->t(_('Nový čas')),
                        'update' => $this->t(_('Upravujete čas č. :id')),
                    ],
                    'buttons' => [
                        'create' => $this->t(_('Nový čas')),
                    ],
                ],
            ],
            'appointment' => [
                'icon' => 'fa-calendar-check',
                'settings' => [
                    'title' => [
                        'create' => $this->t(_('Nová rezervácia')),
                        'update' => $this->t(_('Upravujete rezerváciu č. :id')),
                    ],
                    'buttons' => [
                        'create' => $this->t(_('Nová rezervácia')),
                    ],
                ],
            ],
        ];
    }

    public function getLastTableName()
    {
        return str_singular(last(explode('_', $this->getModel()->getTable())));
    }

    /*
     * Check if given text is translated in given language mutation
     */
    public function hasTranslatedValue($value)
    {
        //If is not string type, we can allow this value
        if ( ! is_string($value) ) {
            return true;
        }

        //We want cache all text value
        return Admin::cache('models.customization.translation.'.$value, function() use ($value) {
            $translations = Admin::cache('models.customization.loadedTranslations', function(){
                $adminLang = AdminLocalization::get();

                //If is selected same language what is in sources files
                if ( $adminLang->slug == 'sk' ) {
                    return false;
                }

                //If language PO file does not exists
                if ( ($poPath = $adminLang->getPoPath())->exists() == false ){
                    return false;
                }

                return Translations::fromPoFile($poPath->basepath);
            });

            //We can allow this value if translations are not available
            if ( $translations === false ) {
                return true;
            }

            //If translation has not been found
            if ( !($translation = $translations->find(null, $value)) ) {
                return true;
            }

            //If translated value has not been found
            return $translation->hasTranslation() === true;
        });
    }

    /**
     * Check if given text has translated value in actual admin language
     * If not, this text wont be setted as customized value
     *
     * @param  string  $value
     * @return  string
     */
    public function t($value)
    {
        if ( $this->hasTranslatedValue($value) ) {
            return $value;
        }

        //This key will be skipped
        return '$$SKIP:continue';
    }

    public function adminModelRender(&$response = [])
    {
        $data = $this->getModelData();

        $name = $this->getLastTableName($this);

        if (array_key_exists($name, $data)) {
            foreach ($data[$name] as $key => $value) {
                //We want skip untranslated value
                if ( $value === '$$SKIP:continue' ) {
                    continue;
                }

                if ( array_get($response, $key) == null ) {
                    array_set($response, $key, $value);
                }
            }
        }

        return $response;
    }
}