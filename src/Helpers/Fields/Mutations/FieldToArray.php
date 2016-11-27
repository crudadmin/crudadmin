<?php
namespace Gogol\Admin\Helpers\Fields\Mutations;

class FieldToArray
{
    public function update( $field )
    {
        if ( is_string($field) )
        {
            $fields = explode('|', $field);

            foreach ($fields as $k => $value)
            {
                $row = explode(':', $value);

                $data[$row[0]] = count($row) == 1 ? true : $row[1];
            }
        } else {
            $data = $field;
        }

        return $data;
    }
}
?>