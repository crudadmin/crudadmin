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
        $template = str_replace("\n", "", $template);

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

            $component_name = strtolower($field['component']);

            if ( ! array_key_exists($component_name, $loaded_components) )
            {
                //Disable throw error on initial admin boot request
                if ( $initial_request === true )
                    continue;

                Ajax::error(sprintf(trans('admin::admin.component-missing'), $component_name, $key), null, null, 500);
            }

            $components[$component_name] = $this->renderFieldComponent( $loaded_components[$component_name] );
        }

        return $components;
    }
}