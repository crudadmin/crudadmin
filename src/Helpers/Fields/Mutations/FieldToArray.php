<?php
namespace Gogol\Admin\Helpers\Fields\Mutations;

class FieldToArray
{
    protected function bindValue($row)
    {
        $count = count($row);

        if ( $count == 1 )
            return true;

        if ( $count == 2 )
            return $row[1];

        if ( $count > 2 )
            return implode(array_slice($row, 1), ':');
    }

    public function update( $field )
    {
        $data = [];

        if ( is_string($field) )
        {
            $fields = explode('|', $field);

            foreach ($fields as $k => $value)
            {
                $row = explode(':', $value);

                if ( array_key_exists($row[0], $data) )
                {
                    //If property has multiple properties yet
                    if ( is_array( $data[$row[0]] ) )
                        $data[$row[0]][] = $this->bindValue($row);
                    else
                        $data[$row[0]] = [$data[$row[0]], $this->bindValue($row)];
                } else {
                    $data[$row[0]] = $this->bindValue($row);
                }
            }
        } else {
            $data = $field;
        }

        return $data;
    }
}
?>