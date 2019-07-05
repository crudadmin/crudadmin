<?php

namespace Admin\Tests\Feature\Migrations;

use Admin\Core\Tests\Concerns\MigrationAssertions;
use Admin\Tests\App\Models\Fields\FieldsType;
use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\TestCase;
use Admin;

class ColumnsTypesTest extends TestCase
{
    use DropDatabase,
        MigrationAssertions;

    public function setUp() : void
    {
        parent::setUp();

        Admin::registerModel(FieldsType::class);

        $this->artisan('admin:migrate');
    }

    /** @test */
    public function test_editor_column()
    {
        $this->assertColumnExists(FieldsType::class, 'editor')
             ->assertColumnType(FieldsType::class, 'editor', 'text')
             ->assertColumnNotNull(FieldsType::class, 'editor', true);
    }

    /** @test */
    public function test_select_column()
    {
        $this->assertColumnExists(FieldsType::class, 'select')
             ->assertColumnType(FieldsType::class, 'select', 'string')
             ->assertColumnNotNull(FieldsType::class, 'select', true)
             ->assertColumnLength(FieldsType::class, 'select', 255);
    }

    /** @test */
    public function test_password_column()
    {
        $this->assertColumnExists(FieldsType::class, 'password')
             ->assertColumnType(FieldsType::class, 'password', 'string')
             ->assertColumnNotNull(FieldsType::class, 'password', true)
             ->assertColumnLength(FieldsType::class, 'password', 255);
    }

    /** @test */
    public function test_radio_column()
    {
        $this->assertColumnExists(FieldsType::class, 'radio')
             ->assertColumnType(FieldsType::class, 'radio', 'string')
             ->assertColumnNotNull(FieldsType::class, 'radio', true)
             ->assertColumnLength(FieldsType::class, 'string', 255);
    }
}
