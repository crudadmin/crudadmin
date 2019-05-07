<?php

namespace Gogol\Admin\Tests\Feature\Model;

use Gogol\Admin\Tests\App\Models\FieldsLevel;
use Gogol\Admin\Tests\App\Models\FieldsMutator;
use Gogol\Admin\Tests\App\Models\FieldsType;
use Gogol\Admin\Tests\TestCase;

class ModelFieldsTest extends TestCase
{
    /** @test */
    public function one_level_fields_avaiability()
    {
        $fields = (new FieldsType)->getFields();

        $this->assertEquals(array_keys($fields), [
            'string', 'text', 'editor', 'select', 'integer', 'decimal', 'file',
            'password', 'date', 'datetime', 'time', 'checkbox', 'radio',
        ]);
    }

    /** @test */
    public function multiple_levels_fields_avaiability()
    {
        $fields = (new FieldsLevel)->getFields();

        $this->assertEquals(array_keys($fields), [
            'field1', 'field2', 'field3', 'field4', 'field5', 'field6', 'field7', 'field8', 'field9',
            'field10', 'field11', 'field12', 'field13', 'field14', 'field15', 'field17', 'field18', 'field19',
            'field20', 'field21', 'field22', 'field23', 'field24', 'field25', 'field26',
        ]);

    }

    /** @test */
    public function mutators_fields_avaiability()
    {
        $fields = (new FieldsMutator)->getFields();

        $this->assertEquals(array_keys($fields), [
            'field1', 'field2', 'field3', 'field4', 'field5', 'field6',
            'field8', 'field9', 'field_end_1', 'field_end_2',
        ]);
    }
}
