<?php

namespace Admin\Helpers\Localization;

use Admin;

class EditorMode
{
    private $sessionEditorKey = 'CAEditor.state';

    /**
     * Is enabled and allowed
     *
     * @return  bool
     */
    public function isEnabled()
    {
        return admin() && Admin::isEnabledLocalization();
    }

    /*
     * Is active mode
     */
    public function isActive()
    {
        return $this->isEnabled() && session($this->sessionEditorKey, false) === true;
    }

    public function setState($state)
    {
        session()->put($this->sessionEditorKey, $state);
        session()->save();
    }
}
