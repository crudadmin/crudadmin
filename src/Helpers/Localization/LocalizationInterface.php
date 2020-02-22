<?php

namespace Admin\Helpers\Localization;

interface LocalizationInterface
{
    /**
     * Localization eloquent
     *
     * @return  string
     */
    public function getModel();

    /**
     * Returns if localization is active
     *
     * @return  bool
     */
    public function isActive();

    /**
     * Check if languages can be loaded automatically
     *
     * @return  bool
     */
    public function canBootAutomatically();

    /**
     * Returns language identifier of actual language
     *
     * @return  string
     */
    public function getLocaleIdentifier();

    /**
     * Returns controller parh for
     *
     * @return  string
     */
    public function gettextJsResourcesMethod();

    /**
     * Check if gettext module is allowed for this localizaiton
     *
     * @return  bool
     */
    public function isGettextAllowed();
}
