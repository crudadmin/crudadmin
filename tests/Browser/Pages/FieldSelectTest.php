<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Gogol\Admin\Tests\App\Models\Articles\ArticlesComment;
use Gogol\Admin\Tests\App\Models\Fields\SelectType;
use Gogol\Admin\Tests\App\Models\Locales\ModelLocalization;
use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Gogol\Admin\Tests\Browser\Traits\SeedTrait;
use Gogol\Admin\Tests\Traits\DropDatabase;
use Laravel\Dusk\Browser;

class FieldSelectTest extends BrowserTestCase
{
    use DropDatabase,
        SeedTrait;

    /** @test */
    public function test_language_select_options_filter()
    {
        ModelLocalization::create([ 'name' => 'sk option', 'language_id' => 1 ]);
        ModelLocalization::create([ 'name' => 'en option', 'language_id' => 2 ]);

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
            [ 'article_id' => 3, 'name' => 'comment 0' ],
            [ 'article_id' => 9, 'name' => 'comment 1' ],
            [ 'article_id' => 9, 'name' => 'comment 2' ],
            [ 'article_id' => 8, 'name' => 'comment 3' ],
            [ 'article_id' => 5, 'name' => 'comment 4' ],
        ]);

        $this->browse(function (Browser $browser) {
            $browser->openModelPage(SelectType::class)
                    ->assertSelectValues(SelectType::class, 'select_filter_by_id', ['hellboy 8'])
                    ->assertDontSee('my filter by auto-table')
                    ->fillForm(SelectType::class, [ 'select_filter_by_id' => 'hellboy 8' ])
                    ->assertSelectValues(SelectType::class, 'comments_id', ['comment 2 9', 'comment 1 9']);
        });
    }
}