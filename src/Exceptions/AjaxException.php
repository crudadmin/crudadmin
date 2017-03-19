<?php

namespace Gogol\Admin\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class AjaxException extends \Illuminate\Http\Exceptions\HttpResponseException
{
    public $code;

    public function __consturct( Response $response, $code )
    {
        $this->code = $code;

        parent::__consturct( $response );
    }

    private function buildErrorResponse()
    {
        return response()->json( Ajax::error( $this->message ) );
    }
}
