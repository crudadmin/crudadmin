<?php

namespace Admin\Tests\Feature\Migrations;

use Admin;
use Admin\Core\Tests\Concerns\MigrationAssertions;
use Admin\Tests\App\Models\Fields\FieldsType;
use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ColumnsTypesTest extends TestCase
{
    use DropDatabase,
        MigrationAssertions;

    public function setUp() : void
    {
        parent::setUp();

        Admin::registerModel(FieldsType::class);

        $this->artisan('admin:migrate');

        $this->setSchema(DB::getSchemaBuilder());
    }

    /** @test */
    public function test_editor_column()
    {
        $this->assertColumnExists('fields_types', 'editor')
             ->assertColumnType('fields_types', 'editor', 'text')
             ->assertColumnNotNull('fields_types', 'editor', true);
    }

    /** @test */
    public function test_select_column()
    {
        $this->assertColumnExists('fields_types', 'select')
             ->assertColumnType('fields_types', 'select', 'string')
             ->assertColumnNotNull('fields_types', 'select', true)
             ->assertColumnLength('fields_types', 'select', 255);
    }

    /** @test */
    public function test_password_column()
    {
        $this->assertColumnExists('fields_types', 'password')
             ->assertColumnType('fields_types', 'password', 'string')
             ->assertColumnNotNull('fields_types', 'password', true)
             ->assertColumnLength('fields_types', 'password', 255);
    }

    /** @test */
    public function test_radio_column()
    {
        $this->assertColumnExists('fields_types', 'radio')
             ->assertColumnType('fields_types', 'radio', 'string')
             ->assertColumnNotNull('fields_types', 'radio', true)
             ->assertColumnLength('fields_types', 'string', 255);
    }
}
