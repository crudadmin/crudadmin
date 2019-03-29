<?php

namespace Gogol\Admin\Traits;

use Illuminate\Filesystem\Filesystem;
use Admin;
use Ajax;

trait FieldComponent
{
    private function getTextBetweenTags($string, $tagname)
    {
        $string = trim($string);

        preg_match("/\<$tagname.*?\>(.*?)\<\/$tagname\>/s", $string, $matches);

        return trim($matches[1]);
    }


    private function renderFieldComponent($path)
    {
        $content = file_get_contents($path);
        $content = preg_replace('#^\s*//.+$#m', '', $content);

        $template = $this->getTextBetweenTags($content, 'template');

        //Fixed CRLF for windows, js does not work with CRLF
        $template = str_replace("\n", "", $template);
        $template = str_replace(["\r\n", "\n", "\r"], '', $template);

        $script = $this->getTextBetweenTags($content, 'script');
        $script = trim(str_replace_first('export default', '', $script));
        $script = str_replace_first('{', "{\ntemplate: \"".addslashes($template)."\",\n", $script);

        return $script;
    }

    /*
     * Return parsed field components
     */
    public function getFieldsComponents($initial_request = false)
    {
        $fields = $this->getFields();

        $loaded_components = Admin::getComponentsTemplates();

        $components = [];

        foreach ($fields as $key => $field)
        {
            if ( ! array_key_exists('component', $field) )
                continue;

            $components_names = explode(',', strtolower($field['component']));

            foreach ($components_names as $component_name)
            {
                if ( ! array_key_exists($component_name, $loaded_components) )
                {
                    //Disable throw error on initial admin boot request
                    if ( $initial_request === true )
                        continue;

                    Ajax::error(sprintf(trans('admin::admin.component-missing'), $component_name, $key), null, null, 500);
                }

                $components[$component_name] = $this->renderFieldComponent( $loaded_components[$component_name] );
            }
        }

        return $components;
    }

    /*
     * Parse vuejs template
     */
    public function renderVueJs($template)
    {
        $filename = trim_end($template, '.vue');
        $filename = str_replace('.', '/', $filename);

        $loaded_components = Admin::getComponentsTemplates();

        if ( array_key_exists($strtolower_filename = strtolower($filename), $loaded_components) )
            $path = $loaded_components[$strtolower_filename];

        elseif ( ($path = resource_path('views/admin/components/'.$filename.'.vue')) && !file_exists($path) )
            $path = resource_path('views/'.$filename.'.vue');

        //Throw ajax error for button component render
        if ($this instanceof \Gogol\Admin\Helpers\Button && ! file_exists($path) ){
            Ajax::error(sprintf(trans('admin::admin.component-missing'), $filename, ''), null, null, 500);
        }

        return $this->renderFieldComponent($path);
    }

    /*
     * Alias for parsing vuejs template
     */
    public function component($template)
    {
        return $this->renderVueJs($template);
    }
}