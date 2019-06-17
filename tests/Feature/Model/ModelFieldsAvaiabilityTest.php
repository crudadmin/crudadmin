<?php

namespace Gogol\Admin\Tests\Feature\Model;

use Gogol\Admin\Tests\App\Models\Fields\FieldsGroup;
use Gogol\Admin\Tests\App\Models\Fields\FieldsMutator;
use Gogol\Admin\Tests\App\Models\Fields\FieldsType;
use Gogol\Admin\Tests\TestCase;

class ModelFieldsAvaiabilityTest extends TestCase
{
    /** @test */
    public function base_fields_avaiability_without_groups()
    {
        $fields = (new FieldsType)->getFields();

        $this->assertEquals(array_keys($fields), [
            'string', 'text', 'editor', 'select', 'integer', 'decimal', 'file',
            'password', 'date', 'datetime', 'time', 'checkbox', 'radio', 'custom',
        ]);
    }

    /** @test */
    public function recursive_groups_and_tabs_fields_avaiability()
    {
        $fields = (new FieldsGroup)->getFields();

        $this->assertEquals(array_keys($fields), [
            'field1', 'field2', 'field3', 'field4', 'field5', 'field6', 'field7', 'field8', 'field9', 'field10',
            'field11', 'field12', 'field13', 'field14', 'field15', 'field16', 'field17', 'field18', 'field19',
            'field20', 'field21', 'field22', 'field23', 'field24', 'field25', 'field26', 'field27', 'field28'
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
