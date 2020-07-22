<?php

namespace Admin\Tests\Browser\Tests;

use Admin;
use Admin\Tests\Browser\DuskBrowser;
use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\App\Models\Tree\Model3;
use Admin\Tests\App\Models\Articles\Tag;
use Admin\Tests\Browser\BrowserTestCase;
use Admin\Tests\Browser\Concerns\SeedTrait;
use Admin\Tests\App\Models\Articles\Article;
use Admin\Tests\App\Models\Fields\FieldsType;
use Admin\Tests\App\Models\Fields\FieldsRelation;

class TableSearchTest extends BrowserTestCase
{
    use DropDatabase,
        SeedTrait;

    /** @test */
    public function test_searchbar_text_integers_dates_and_intervals()
    {
        $this->createArticleMoviesList();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Article::class);

            //Search for word
            $browser->type('[data-search-bar] input[data-search-text]', 'man')
                    ->waitUntilMissing('tr[data-id="12"]') //wait until john wick dissapear
                    ->assertColumnRowData(Article::class, 'name', ['superman', 'spider-man', 'hastrman', 'aquaman']);

            //Search by column
            $browser->click('[data-search-bar] button.dropdown-toggle')
                    ->click('[data-search-bar] [data-field="score"]')
                    ->valueWithEvent('[data-search-bar] input[data-search-text]', 9)
                    ->waitUntilMissing('tr[data-id="7"]')->waitFor('tr[data-id="10"]') //wait until superman dissapear from previous search
                    ->assertColumnRowData(Article::class, 'name', ['spider-man']);

            //Search by interval from 9 to 11
            $browser->click('[data-interval] button')
                    ->type('[data-search-bar] input[data-search-interval-text]', 11)
                    ->waitFor('tr[data-id="12"]') //wait for john wick
                    ->assertColumnRowData(Article::class, 'name', ['john wick', 'superman', 'spider-man']);

            //Close interval and test searching by date
            $browser->click('[data-interval] button')
                    ->click('[data-search-bar] button.dropdown-toggle')
                    ->click('[data-search-bar] [data-field="created_at"]')
                    ->click('[data-search-bar] input[data-search-date]')->pause(100)
                    ->clickDatePicker(date('16.m.Y'))
                    ->waitFor('tr[data-id="6"]') //wait for star is born will be loaded
                    ->assertColumnRowData(Article::class, 'name', ['star is born']);

            //Search by interval date 16 to 20
            $browser->click('[data-interval] button')
                    ->click('[data-search-bar] input[data-search-interval-date]')->pause(100)
                    ->clickDatePicker(date('20.m.Y'))
                    ->waitFor('tr[data-id="9"]') //wait till hellboy will be loaded
                    ->assertColumnRowData(Article::class, 'name', ['hellboy', 'barefoot', 'hastrman', 'star is born']);
        });
    }

    /** @test */
    public function test_searchbar_selects_and_relations()
    {
        $this->createArticleMoviesList();
        $this->createTagList();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Tag::class)

                    //Test select filter
                    ->click('[data-search-bar] button.dropdown-toggle')
                    ->click('[data-search-bar] [data-field="type"]')
                    ->setChosenValue('[data-search-bar] [data-search-select]', 'moovie')
                    ->waitUntilMissing('tr[data-id="10"]') //wait until missing last item which wont be in search
                    ->assertColumnRowData(Tag::class, 'article_id', ['avengers 1', 'avengers 1', 'avengers 1', 'titanic 1'])

                    //Test belongsTo relation filter
                    ->click('[data-search-bar] button.dropdown-toggle')
                    ->click('[data-search-bar] [data-field="article_id"]')
                    ->setChosenValue('[data-search-bar] [data-search-select]', 'avengers')
                    ->waitFor('tr[data-id="5"]')->waitUntilMissing('tr[data-id="1"]') //wait will blog row will be loaded
                    ->assertColumnRowData(Tag::class, 'type', ['blog', 'moovie', 'moovie', 'moovie'])

                    //Test search with imaginary column
                    ->click('[data-search-bar] button.dropdown-toggle')
                    ->click('[data-search-bar] [data-field=""]')
                    ->type('[data-search-bar] input[data-search-text]', 'man')
                    ->waitUntilMissing('tr[data-id="5"]')->waitFor('tr[data-id="10"]')
                    ->assertColumnRowData(Tag::class, 'article_id', ['hastrman 1', 'aquaman 1']);
        });
    }
}
