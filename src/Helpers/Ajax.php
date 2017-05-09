<?php

namespace Gogol\Admin\Helpers;

use Gogol\Admin\Exceptions\AjaxException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Log;

class Ajax {

    static function success($message = null, $title = null, $data = null, $code = 200)
    {
        return self::message(
            $message ? $message : _('Zmeny boli úspešne uložené.'),
            $title,
            'success',
            $data,
            $code
        );
    }

    static function error($message = null, $title = null, $data = null, $code = 200)
    {
        return self::message(
            $message ? $message : _('Nastala nečakana chyba, skúste neskôr prosím.'),
            $title ? $title : _('Upozornenie'),
            'error',
            $data,
            $code
        );
    }

    static function message($message = null, $title = null, $type = 'info', $data = null, $code = 200)
    {
        $array = [
            'type' => $type,
            'title' => $title ? $title : _('Informácia'),
            'message' => $message,
        ];

        if ( isset( $data ) )
            $array['data'] = $data;


        throw new AjaxException( response()->json($array, $code), $code );
    }

    static function permissionsError()
    {
        return self::error( 'Nemate právomoc k pristúpeniu do tejto sekcie.', null, null, 401 );
    }

    /*
     * Return error according to laravel debug mode
     */
    static function mysqlError(\Exception $e)
    {
        //Log error
        Log::error( $e );

        if ( env('APP_DEBUG') == true )
            Ajax::error('Nastala nečakaná chyba, pravdepodobne ste nespústili migráciu modelov pomocou príkazu:<br><strong>php artisan admin:migrate</strong><br><br><small>'.e($e->getMessage()).'</small>', null, null, 500);

        return Ajax::error('Nastala nečakaná chyba. Váš administrátor pravdepodobne zabudol aktualizovať databázu. Ak táto chyba pretrváva, kontaktujte ho.<br><br><small>'.e($e->getMessage()).'</small>', null, null, 500);
    }

}

?>