<?php
namespace Gogol\Admin\Helpers;

class Ajax {

    static function success($message = null, $title = null, $data = null)
    {
        return self::message(
            $message ? $message : _('Zmeny boli úspešne uložené.'),
            $title,
            'success',
            $data
        );
    }

    static function error($message = null, $title = null, $data = false)
    {
        return self::message(
            $message ? $message : _('Nastala nečakana chyba, skúste neskôr prosím.'),
            $title ? $title : _('Upozornenie'),
            'error',
            $data
        );
    }

    static function message($message = null, $title = null, $type = 'info', $data = null)
    {
        $array = [
            'type' => $type,
            'title' => $title ? $title : _('Informácia'),
            'message' => $message,
        ];

        if ( isset( $data ) )
            $array['data'] = $data;

        return response()->json($array);
    }

}

?>