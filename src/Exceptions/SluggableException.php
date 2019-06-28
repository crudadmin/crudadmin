<?php

namespace Admin\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class SluggableException extends \Illuminate\Http\Exceptions\HttpResponseException
{
    public function __consturct(Response $response)
    {
        parent::__consturct($response);
    }
}
