<?php

namespace Gogol\Admin\Tests\Feature\Model;

use Gogol\Admin\Tests\App\Models\FieldsType;
use Gogol\Admin\Tests\TestCase;

class ModelFieldsTypesTest extends TestCase
{
    private $model;

    protected function setUp() : void
    {
        parent::setUp();

        $this->model = new FieldsType;
    }

    /** @test */
    public function string()
    {
        $this->assertEquals($this->model->getField('string'), [
            'name' => 'my string field',
            'type' => 'string',
            'max' => '255',
            'value' => null,
        ]);
    }

    /** @test */
    public function text()
    {
        $this->assertEquals($this->model->getField('text'), [
            'name' => 'my text field',
            'type' => 'text',
            'value' => null,
        ]);
    }

    /** @test */
    public function editor()
    {
        $this->assertEquals($this->model->getField('editor'), [
            'name' => 'my editor field',
            'type' => 'editor',
            'hidden' => true,
            'value' => null,
        ]);
    }

    /** @test */
    public function select()
    {
        $this->assertEquals($this->model->getField('select'), [
            'name' => 'my select field',
            'type' => 'select',
            'options' => [],
            'value' => null,
        ]);
    }

    /** @test */
    public function integer()
    {
        $this->assertEquals($this->model->getField('integer'), [
            'name' => 'my integer field',
            'type' => 'integer',
            'integer' => true,
            'max' => '4294967295',
            'value' => null,
        ]);
    }

    /** @test */
    public function decimal()
    {
        $this->assertEquals($this->model->getField('decimal'), [
            'name' => 'my decimal field',
            'type' => 'decimal',
            'numeric' => true,
            'value' => null,
        ]);
    }

    /** @test */
    public function file()
    {
        $this->assertEquals($this->model->getField('file'), [
            'name' => 'my file field',
            'type' => 'file',
            'max' => '10240',
            'file' => true,
            'nullable' => true,
            'value' => null,

        ]);
    }

    /** @test */
    public function password()
    {
        $this->assertEquals($this->model->getField('password'), [
            'name' => 'my password field',
            'type' => 'password',
            'hidden' => true,
            'value' => null,

        ]);
    }

    /** @test */
    public function date()
    {
        $this->assertEquals($this->model->getField('date'), [
            'name' => 'my date field',
            'type' => 'date',
            'date_format' => 'd.m.Y',
            'nullable' => true,
            'value' => null,

        ]);
    }

    /** @test */
    public function datetime()
    {
        $this->assertEquals($this->model->getField('datetime'), [
            'name' => 'my datetime field',
            'type' => 'datetime',
            'date_format' => 'd.m.Y H:i',
            'nullable' => true,
            'value' => null,

        ]);
    }

    /** @test */
    public function time()
    {
        $this->assertEquals($this->model->getField('time'), [
            'name' => 'my time field',
            'type' => 'time',
            'date_format' => 'H:i',
            'nullable' => true,
            'value' => null,

        ]);
    }

    /** @test */
    public function checkbox()
    {
        $this->assertEquals($this->model->getField('checkbox'), [
            'name' => 'my checkbox field',
            'type' => 'checkbox',
            'boolean' => true,
            'value' => null,

        ]);
    }

    /** @test */
    public function radio()
    {
        $this->assertEquals($this->model->getField('radio'), [
            'name' => 'my radio field',
            'type' => 'radio',
            'options' => [],
            'value' => null,
        ]);
    }
}
