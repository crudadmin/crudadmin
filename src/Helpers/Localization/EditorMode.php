<?php

namespace Admin\Helpers\Localization;

use Admin;
use Localization;

class EditorMode
{
    private $sessionEditorKey = 'CAEditor.state';

    protected $visibleRoutes = [];

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
        return Admin::isEnabledFrontendEditor() && session($this->sessionEditorKey, false) === true;
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

    /**
     * Add visible routes in view templates
     *
     * @param  string  $action
     * @param  string  $url
     */
    public function addVisibleRoute($action, $url)
    {
        $this->visibleRoutes[$action] = $url;
    }

    /*
     * Returns visible routes in view templates
     */
    public function getVisibleRoutes()
    {
        return array_map(function($url){
            return url($url ?: []);
        }, $this->visibleRoutes);
    }
}
