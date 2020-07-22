<?php

namespace Admin\Controllers\Crud\Concerns;

use Admin;

trait CRUDResponse
{
    /*
     * Returns errors from admin buffer and admin request buffer
     */
    protected function getRequestErrors()
    {
        return array_merge((array) Admin::get('errors'), (array) Admin::get('errors.request'));
    }

    /*
     * Return simple message, or when is errors avaliable then shows them
     */
    protected function responseMessage($sentense)
    {
        if (count($this->getRequestErrors())) {
            return $sentense.' '.trans('admin::admin.with-errors').':<br>'.implode('<br>', $this->getRequestErrors());
        }

        return $sentense.'.';
    }

    protected function responseType()
    {
        return count($this->getRequestErrors()) ? 'info' : 'success';
    }
}

?>