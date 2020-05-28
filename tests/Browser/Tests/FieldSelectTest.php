<?php

namespace Admin\Tests\Browser\Tests;

use Laravel\Dusk\Browser;
use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\Browser\BrowserTestCase;
use Admin\Tests\Browser\Concerns\SeedTrait;
use Admin\Tests\App\Models\Fields\SelectType;
use Admin\Tests\App\Models\Articles\ArticlesComment;
use Admin\Tests\App\Models\Locales\ModelLocalization;

class FieldSelectTest extends BrowserTestCase
{
    use DropDatabase,
        SeedTrait;

    /** @test */
    public function test_language_select_options_filter()
    {
        ModelLocalization::create(['name' => 'sk option', 'language_id' => 1]);
        ModelLocalization::create(['name' => 'en option', 'language_id' => 2]);

        $this->browse(function (Browser $browser) {
            $browser->openModelPage(SelectType::class)
                    ->assertSelectValues(SelectType::class, 'langs_id', ['sk option'])
                    ->changeRowLanguage('en')
                    ->assertSelectValues(SelectType::class, 'langs_id', ['en option']);
        });
    }

    /** @test */
    public function test_default_select_values()
    {
        $this->browse(function (Browser $browser) {
            $browser->openModelPage(SelectType::class)
                    ->assertValue('[name="score_input"]', 8);
        });
    }

    /** @test */
    public function test_default_filter_by_value_and_filter_by_on_change()
    {
        $this->createArticleMoviesList();

        ArticlesComment::insert([
            ['article_id' => 3, 'name' => 'comment 0'],
            ['article_id' => 9, 'name' => 'comment 1'],
            ['article_id' => 9, 'name' => 'comment 2'],
            ['article_id' => 8, 'name' => 'comment 3'],
            ['article_id' => 5, 'name' => 'comment 4'],
        ]);

        $this->browse(function (Browser $browser) {
            $browser->openModelPage(SelectType::class)

                    //Test default values in filter select
                    ->assertSelectValues(SelectType::class, 'select_filter_by_id', ['hellboy 8'])
                    ->assertDontSee('my filter by auto-table')
                    ->fillForm(SelectType::class, ['select_filter_by_id' => 'hellboy 8'])
                    ->assertSelectValues(SelectType::class, 'comments_id', ['comment 2 9', 'comment 1 9'])

                    //Test save form, and check if values are reseted, and then again available
                    ->fillForm(SelectType::class, ['score_input' => 6])
                    ->assertSelectValues(SelectType::class, 'select_filter_by_id', ['hastrman 6'])
                    ->fillForm(SelectType::class, ['select_filter_by_id' => 'hastrman 6'])
                    ->submitForm()->assertSeeSuccess(trans('admin::admin.success-created'))->closeAlert()

                    //After save, check if default value is again seted
                    ->assertSelectValues(SelectType::class, 'select_filter_by_id', ['hellboy 8']);
        });
    }

    /** @test */
    public function test_add_row_relation_from_field()
    {
        ModelLocalization::create(['name' => 'sk option', 'language_id' => 1]);
        ModelLocalization::create(['name' => 'en option', 'language_id' => 2]);

        $this->browse(function (Browser $browser) {
            $browser->openModelPage(SelectType::class)

                    //Check if exists add relation row button
                    ->assertElementExists('[data-field="langs_id"] [data-add-relation-row]')

                    //Open relation model and create new row
                    ->jsClick('[data-field="langs_id"] [data-add-relation-row]')->pause(500)
                    ->fillForm(ModelLocalization::class, ['name' => 'new sk option value'], 'sk')->submitForm()
                    ->assertSeeSuccess(trans('admin::admin.success-created'))->closeAlert()

                    //Update existing row
                    ->openRow(1, ModelLocalization::class)
                    ->fillForm(ModelLocalization::class, ['name' => 'updated existing sk row'], 'sk')
                    ->saveForm()->closeAlert()->jsClick('[data-form="model_localizations"] button[data-create-new-row]')
                    ->jsClick('.modal-header button.close')->pause(400) //need be duration, because of bug when changing language

                    //Check if new created row is selected in select
                    ->assertSelectValues(SelectType::class, 'langs_id', ['new sk option value', 'updated existing sk row'])
                    ->assertHasFormValues(SelectType::class, ['langs_id' => 3], 'sk')

                    //On english language change check select options, then add english row and check new item in options
                    ->changeRowLanguage('en')
                    ->assertSelectValues(SelectType::class, 'langs_id', ['en option'])
                    ->jsClick('[data-field="langs_id"] [data-add-relation-row]')->pause(500)
                    ->fillForm(ModelLocalization::class, ['name' => 'new en option value'], 'en')->submitForm()
                    ->assertSeeSuccess(trans('admin::admin.success-created'))->closeAlert()
                    ->click('.modal-header button.close')->pause(400) //need be duration, because of bug when changing language
                    ->assertSelectValues(SelectType::class, 'langs_id', ['new en option value', 'en option'])

                    //On changing language to slovak, check options
                    ->changeRowLanguage('sk')
                    ->assertSelectValues(SelectType::class, 'langs_id', ['new sk option value', 'updated existing sk row']);
        });
    }
}
