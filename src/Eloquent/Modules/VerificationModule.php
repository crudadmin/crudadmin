<?php

namespace Admin\Eloquent\Modules;

use Admin\Core\Eloquent\Concerns\AdminModelModule;
use Admin\Core\Eloquent\Concerns\AdminModelModuleSupport;

class VerificationModule extends AdminModelModule implements AdminModelModuleSupport
{
    public function isActive($model)
    {
        return count($model->getLoginVerifications()) > 0;
    }

    public function mutateFields($fields)
    {
        $fields->push([
            'verification_method' => 'name:Verifikácia prihlásenia pomocou|type:select|required|default:none',
        ]);
    }

    public function setOptionsProperty($options = [])
    {
        $availableMethods = $this->getModel()->getLoginVerifications();

        $methods = array_intersect_key([
            'email' => _('E-mail'),
            'sms' => _('Sms kódom'),
        ], array_flip($availableMethods));

        return array_merge($options ?: [], [
            'verification_method' => array_merge([
                'none' => _('Bez verifikácie'),
            ], $methods),
        ]);
    }
}
