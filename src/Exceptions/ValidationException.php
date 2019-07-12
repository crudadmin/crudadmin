<?php

namespace Admin\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ValidationException extends \Illuminate\Http\Exceptions\HttpResponseException
{
    public function __consturct(Response $response)
    {
        parent::__consturct($response);
    }
}
