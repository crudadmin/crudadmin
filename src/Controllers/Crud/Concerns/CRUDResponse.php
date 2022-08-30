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

    public function hasAdditionalMessages()
    {
        return count(array_merge(
            $this->getRequestMessages('error'),
            $this->getRequestMessages('notice')
        )) > 0;
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
            return 'warning';
        }

        if ( count($this->getRequestMessages('notice')) ){
            return 'info';
        }

        return 'success';
    }
}

?>