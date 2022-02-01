<?php

namespace Admin\Controllers\Crud\Concerns;

use Admin;

trait CRUDResponse
{
    /**
     * Returns errors from admin buffer and admin request buffer
     *
     * @param  string  $type error|notice
     * @return  [type]
     */
    protected function getRequestMessages($type = 'error')
    {
        return array_merge(
            (array) Admin::get('request.'.$type)
        );
    }

    /*
     * Return simple message, or when is errors avaliable then shows them
     */
    protected function responseMessage($sentense)
    {
        if (count($errors = $this->getRequestMessages('error'))) {
            return $sentense.' '.trans('admin::admin.with-errors').':<br>'.implode('<br>', $errors);
        }

       if (count($notices = $this->getRequestMessages('notice'))) {
            return $sentense.' '._('s nasledujúcimi hláseniami').':<br>'.implode('<br>', $notices);
        }

        return $sentense.'.';
    }

    protected function responseType()
    {
        if ( count($this->getRequestMessages('error')) ){
            return 'info';
        }

        return 'success';
    }
}

?>