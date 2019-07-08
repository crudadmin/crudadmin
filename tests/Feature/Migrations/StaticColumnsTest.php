<?php

namespace Admin\Core\Tests\Feature\Migrations;

use AdminCore;
use Admin\Core\Tests\Concerns\MigrationAssertions;
use Admin\Tests\App\Models\Locales\ModelLocalization;
use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\TestCase;
use Illuminate\Support\Facades\DB;

class StaticColumnsTest extends TestCase
{
    use DropDatabase,
        MigrationAssertions;

    public function setUp() : void
    {
        parent::setUp();

        AdminCore::registerModel([
            ModelLocalization::class,
        ]);

        $this->artisan('admin:migrate');

        $this->setSchema(DB::getSchemaBuilder());
    }

    /** @test */
    public function test_order_column()
    {
        $this->assertColumnExists('model_localizations', '_order')
             ->assertColumnType('model_localizations', '_order', 'integer')
             ->assertColumnNotNull('model_localizations', '_order', true)
             ->assertColumnUnsigned('model_localizations', '_order', true);
    }

    /** @test */
    public function test_deleted_at_column()
    {
        $this->assertColumnExists('model_localizations', 'language_id')
             ->assertColumnType('model_localizations', 'language_id', 'integer')
             ->assertColumnNotNull('model_localizations', 'language_id', false)
             ->assertColumnUnsigned('model_localizations', 'language_id', true)
             ->assertHasForeignKey('model_localizations', 'model_localizations_language_id_foreign');
    }
}
