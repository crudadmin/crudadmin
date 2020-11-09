<?php

namespace Admin\Eloquent\Concerns;

use Ajax;
use Admin;
use Admin\Helpers\Button;
use Admin\Helpers\Layout;

trait VueComponent
{
    /*
     * Return parsed field component, or multiple components
     */
    public function getFieldsComponents($initialRequest = false)
    {
        $fields = $this->getFields();

        $components = [];

        foreach ($fields as $key => $field) {
            $this->importComponentFromAttribute($initialRequest, 'component', $key, $field, $components);
            $this->importComponentFromAttribute($initialRequest, 'sub_component', $key, $field, $components);
            $this->importComponentFromAttribute($initialRequest, 'column_component', $key, $field, $components);
        }

        return $components;
    }

    private function importComponentFromAttribute($initialRequest, $attribute, $key, $field, &$components)
    {
        if (! array_key_exists($attribute, $field)) {
            return;
        }

        $componentsNames = explode(',', $field[$attribute]);

        foreach ($componentsNames as $name) {
            if (! ($path = $this->getComponentRealPath($name))) {
                //Disable throw error on initial admin boot request
                if ($initialRequest === true) {
                    return;
                }

                return;
            }

            if ( $data = $this->renderVuejsComponent($path) ) {
                $components[strtolower($name)] = $data;
            }
        }
    }

    /*
     * Render component
     */
    private function renderVuejsComponent($path)
    {
        $content = file_get_contents($path);
        $content = preg_replace('#^\s*//.+$#m', '', $content);

        $template = $this->getTextBetweenTags($content, 'template');

        //Fixed CRLF for windows, js does not work with CRLF
        $template = str_replace("\n", '', $template);
        $template = str_replace(["\r\n", "\n", "\r"], '', $template);

        $script = $this->getTextBetweenTags($content, 'script');
        $script = trim(str_replace_first('export default', '', $script));
        $script = str_replace_first('{', "{\ntemplate: \"".addslashes($template)."\",\n", $script);

        return $script;
    }

    /*
     * Get everything between given element
     */
    private function getTextBetweenTags($string, $tagname)
    {
        $string = trim($string);

        preg_match("/\<$tagname.*?\>(.*?)\<\/$tagname\>/s", $string, $matches);

        return trim(@$matches[1]);
    }

    /*
     * Check if component does exists in specific locations
     */
    private function getComponentRealPath($originalFilename)
    {
        $filename = trim_end($originalFilename, '.vue');
        $filename = str_replace('.', '/', $filename);

        //Try components from root of given component directories from config
        $locations = array_map(function ($path) use ($filename) {
            return trim_end($path, '/').'/'.$filename.'.vue';
        }, Admin::getComponentsPaths());

        //Try also files with absolute paths
        if ($originalFilename[0] == '/') {
            $locations[] = $originalFilename.'.vue';
            $locations[] = $originalFilename;
        }

        //Try additional feature components path
        if (method_exists($this, 'getComponentPaths')) {
            $locations[] = trim_end($this->getComponentPaths(), '/').'/'.$filename.'.vue';
        }

        //Get component with directory from views path (in case someone would type admin/components/template.vue)
        $locations[] = resource_path('views/'.$filename.'.vue');

        //Try all possible combinations where would be stored component
        foreach ($locations as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        //Get all loaded lowercase components
        $loadedComponents = Admin::getComponentsFiles();

        //Get component from loaded components list with lowercase file support
        if (array_key_exists($strtolower_filename = strtolower($filename), $loadedComponents)) {
            return $loadedComponents[$strtolower_filename];
        }
    }

    /*
     * Parse vuejs template
     */
    public function renderVueJs($filename)
    {
        $path = $this->getComponentRealPath($filename);

        //Throw ajax error for button or layout component render
        if ($path === null) {
            return $filename;
        }

        return $this->renderVuejsComponent($path);
    }

    /*
     * Alias for parsing vuejs template
     */
    public function component($template)
    {
        return $this->renderVueJs($template);
    }
}
