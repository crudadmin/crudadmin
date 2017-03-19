<?php

namespace Gogol\Admin\Helpers;

use Gogol\Admin\Exceptions\AjaxException;

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

}

?>