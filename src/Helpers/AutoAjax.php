<?php

namespace Admin\Helpers;

use AutoAjax\AutoAjax as BaseAutoAjax;
use Exception;
use Admin;
use Log;

class AutoAjax extends BaseAutoAjax
{
    protected $toast = false;

    /**
     * Set default types
     */
    public function boot()
    {
        //Set global messages
        $this->setMessage('error', _('Nastala nečakaná chyba, skúste neskôr prosím.'));
        $this->setMessage('success', _('Zmeny boli úspešne uložené.'));

        //Mutate responses
        $this->setEvent('onResponse', function($response){
            $response['toast'] = $this->toast;

            return $response;
        });

        $this->setEvent('onMessage', function($autoAjax){
            if ( $this->message && $autoAjax->toast === false ) {
                $autoAjax->title = $autoAjax->title ?: trans('admin::admin.info');
            }
        });

        $this->setEvent('onSuccess', function($autoAjax){
            $autoAjax->type = $autoAjax->type ?: 'success';
        });

        $this->setEvent('onError', function($autoAjax){
            $autoAjax->type = $autoAjax->type ?: 'error';

            if ( $autoAjax->toast === false ) {
                $autoAjax->title = $autoAjax->title ?: trans('admin::admin.warning');
            }
        });
    }

    public function toast($state)
    {
        $this->toast = $state;

        return $this;
    }

    /*
     * Return error according to laravel debug mode
     */
    public function mysqlError(Exception $e)
    {
        //Log error
        Log::error($e);

        if (env('APP_DEBUG') == true) {
            return $this->error(trans('admin::admin.migrate-error').'<br><strong>php artisan admin:migrate</strong><br><br><small>'.e($e->getMessage()).'</small>', 500);
        }

        return $this->error(trans('admin::admin.db-error').'<br><br><small>'.e($e->getMessage()).'</small>', 500);
    }

    public function permissionsError()
    {
        return $this->error(_('Nemáte právomoc k pristúpeniu do tejto sekcie.'), 401);
    }

    /**
     * Push warning message into admin request error
     *
     * @param  string  $message
     * @param  string  $type (notice|error)
     */
    public function pushMessage($message, $type = 'notice')
    {
        Admin::push('request.'.$type, $message);
    }
}
