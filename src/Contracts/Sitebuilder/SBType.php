<?php

namespace Admin\Contracts\Sitebuilder;

use Admin\Core\Fields\Mutations\FieldToArray;
use Admin\Eloquent\AdminModel;

class SBType
{
    /**
     * Wrap block content into block wrapper
     *
     * @var  bool
     */
    protected $wrapper = true;

    /**
     * Group all block of same type into one block wrapper
     *
     * @var  bool
     */
    protected $groupBlocks = false;

    /**
     * Wrap block content into block wrapper
     *
     * @return  bool
     */
    public function hasWrapper()
    {
        return $this->wrapper;
    }

    /**
     * Group all block of same type into one block wrapper
     *
     * @return  bool
     */
    public function hasGroupedBlocks()
    {
        return $this->groupBlocks;
    }

    /**
     * Columns and group prefix for given type builder type.
     * Receive it from prefix property, or generate by class name
     *
     * @return  string
     */
    public function getPrefix()
    {
        if ( property_exists($this, 'prefix') ){
            return $this->prefix;
        }

        return str_slug(class_basename(get_class($this)), '_');
    }

    /**
     * Returns icon name from font-awesome library
     *
     * @return  string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Get mutated fields. Remove required properties, and replace them with required_if,type,xy
     *
     * @return  array
     */
    public function getMutatedFields()
    {
        $fields = $this->getFields();

        foreach ($fields as $key => $field) {
            $field = (new FieldToArray)->update($field);

            //Remove required property and replace it with required only when type is selected
            if ( isset($field['required']) && $field['required'] === true ){
                unset($field['required']);

                $field['required_if'] = 'type,'.$this->getPrefix();
            }

            $fields[$key] = $field;
        }

        return $fields;
    }

    /**
     * Render block view by given row data model
     *
     * @param  AdminModel  $row
     * @param  int  $increment
     *
     * @return  void
     */
    public function renderView(AdminModel $row, int $increment = null)
    {
        $data = [];

        foreach ($row->getFields() as $key => $field) {
            $prefixValue = $this->getPrefix().'_';
            $prefixLength = strlen($prefixValue);

            if ( substr($key, 0, $prefixLength) === $prefixValue ) {
                $keyName = substr($key, $prefixLength);

                //Get block field value
                $value = $row->{$key};

                //Run fields mutators for given block field value
                if ( method_exists($this, $methodName = 'get'.$keyName.'attribute') ){
                    $value = $this->{$methodName}($value);
                }

                $data[$keyName] = $value;
            }
        }

        //Pass data into view model and render it
        return view('admin::sitebuilder/'.$this->getPrefix(), $data + [
            'increment' => $increment,
            'row' => $row,
        ])->render();
    }
}