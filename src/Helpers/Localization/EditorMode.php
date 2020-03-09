<?php

namespace Admin\Helpers\Localization;

use Admin;
use Localization;

class EditorMode
{
    private $sessionEditorKey = 'CAEditor.state';

    /**
     * Is enabled and allowed
     *
     * @return  bool
     */
    public function hasAccess($localization = Localization::class)
    {
        return (
            admin() && admin()->hasAccess(get_class($localization::getModel()), 'update')
            && Admin::isEnabledLocalization()
        );
    }

    /*
     * Is active mode
     */
    public function isActive()
    {
        return session($this->sessionEditorKey, false) === true;
    }

    /*
     * Is active mode
     */
    public function isActiveTranslatable()
    {
        return $this->hasAccess() && $this->isActive();
    }

    public function setState($state)
    {
        session()->put($this->sessionEditorKey, $state);
        session()->save();
    }
}
