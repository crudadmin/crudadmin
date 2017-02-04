<?php

namespace Gogol\Admin\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class ValidationException extends \Illuminate\Http\Exception\HttpResponseException
{
    public function __consturct(Response $response)
    {
        parent::__consturct($response);
    }
}
