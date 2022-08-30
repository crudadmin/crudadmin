<?php

namespace Admin\Tests\Browser\Tests;

use Admin;
use Admin\Tests\Browser\DuskBrowser;
use Admin\Tests\Concerns\DropDatabase;
use Admin\Tests\Browser\BrowserTestCase;
use Admin\Tests\Browser\Concerns\SeedTrait;
use Admin\Tests\App\Models\Articles\Article;
use Admin\Tests\App\Models\Fields\FieldsType;

class TablePaginationTest extends BrowserTestCase
{
    use DropDatabase,
        SeedTrait;

    /** @test */
    public function test_rows_limit()
    {
        //Create 100 articles
        factory(FieldsType::class, 100)->create();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(FieldsType::class);

            //Check if default rows grid has 10 items
            $this->assertCount(10, $browser->getRows(FieldsType::class));

            //Paginate on 50 items, wait for and check if rows changed
            $browser->changeRowsLimit(50)
                    ->waitFor('tr[data-id="51"]'); //rows are in reversed order
            $this->assertCount(50, $browser->getRows(FieldsType::class));

            //Paginate on 100 items, wait for and check if rows changed
            $browser->changeRowsLimit(100)
                    ->waitFor('tr[data-id="10"]'); //rows are in reversed order
            $this->assertCount(100, $browser->getRows(FieldsType::class));

            //Check if same limit is set after page reload
            $browser->script('window.location.reload()');
            $this->assertCount(100, $browser->getRows(FieldsType::class));
        });
    }

    /** @test */
    public function test_pagination()
    {
        $this->createArticleMoviesList();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Article::class)
                    ->changeRowsLimit(5)
                    ->waitUntilMissing('tr[data-id="1"]'); //Wait until missing last row in table

            //Check if pagination has correct number of pages
            $browser->assertHasClass('[data-pagination] li:contains(1)', 'active');
            $paginationItems = $browser->script('return $("[data-pagination] li:not([data-pagination-next]):not([data-pagination-prev])")')[0];
            $this->assertCount(3, $paginationItems);

            //Test next page button
            $browser->click('[data-pagination-next] a')
                    ->waitUntilMissing('tr[data-id="12"]') //wait until first row in previous page will be missing (john-wick)
                    ->assertHasClass('[data-pagination] li:contains(2)', 'active')
                    ->assertColumnRowData(Article::class, 'name', ['hastrman', 'star is born', 'aquaman', 'captain marvel', 'shrek']);

            //Test prev page button
            $browser->click('[data-pagination-prev] a')
                    ->assertHasClass('[data-pagination] li:contains(1)', 'active')
                    ->waitUntilMissing('tr[data-id="7"]') //wait until first row in previous page will be missing (hastrman)
                    ->assertColumnRowData(Article::class, 'name', ['john wick', 'superman', 'spider-man', 'hellboy', 'barefoot']);

            //Test number page button
            $browser->jsClick('[data-pagination] li:contains(3) a')
                    ->waitUntilMissing('tr[data-id="12"]') //wait until first row in previous page will be missing (john-wick)
                    ->assertHasClass('[data-pagination] li:contains(3)', 'active')
                    ->assertColumnRowData(Article::class, 'name', ['avengers', 'titanic']);
        });
    }

    /** @test */
    public function test_pagination_skipping_pages_when_is_lot_of_pages()
    {
        factory(Article::class, 500)->create();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Article::class)
                    ->changeRowsLimit(5)->pause(100);

            $paginationItems = $browser->script('return $("[data-pagination] li:not([data-pagination-next]):not([data-pagination-prev])")')[0];

            $this->assertCount(12, $paginationItems);
        });
    }

    /** @test */
    public function test_table_sorting()
    {
        factory(FieldsType::class, 10)->create();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(FieldsType::class)
                    //Sort id fields
                    ->click('[data-table-head] .th-id')
                    ->assertRowsSortOrder(FieldsType::class, 'id', 'asc')
                    ->click('[data-table-head] .th-id')
                    ->assertRowsSortOrder(FieldsType::class, 'id', 'desc')

                    //Sort texts fields
                    ->click('[data-table-head] .th-string')
                    ->assertRowsSortOrder(FieldsType::class, 'string', 'asc')
                    ->click('[data-table-head] .th-string')
                    ->assertRowsSortOrder(FieldsType::class, 'string', 'desc')

                    //Sort string field
                    ->click('[data-table-head] .th-select')
                    ->assertRowsSortOrder(FieldsType::class, 'select', 'asc')
                    ->click('[data-table-head] .th-select')
                    ->assertRowsSortOrder(FieldsType::class, 'select', 'desc')

                    //Sort integer field
                    ->click('[data-table-head] .th-integer')
                    ->assertRowsSortOrder(FieldsType::class, 'integer', 'asc')
                    ->click('[data-table-head] .th-integer')
                    ->assertRowsSortOrder(FieldsType::class, 'integer', 'desc')

                    //Sort decimal field
                    ->click('[data-table-head] .th-decimal')
                    ->assertRowsSortOrder(FieldsType::class, 'decimal', 'asc')
                    ->click('[data-table-head] .th-decimal')
                    ->assertRowsSortOrder(FieldsType::class, 'decimal', 'desc')

                    //Sort date field
                    ->click('[data-table-head] .th-date')
                    ->assertRowsSortOrder(FieldsType::class, 'date', 'asc')
                    ->click('[data-table-head] .th-date')
                    ->assertRowsSortOrder(FieldsType::class, 'date', 'desc')

                    //Sort datetime field
                    ->click('[data-table-head] .th-datetime')
                    ->assertRowsSortOrder(FieldsType::class, 'datetime', 'asc')
                    ->click('[data-table-head] .th-datetime')
                    ->assertRowsSortOrder(FieldsType::class, 'datetime', 'desc')

                    //Sort time field
                    ->click('[data-table-head] .th-time')
                    ->assertRowsSortOrder(FieldsType::class, 'time', 'asc')
                    ->click('[data-table-head] .th-time')
                    ->assertRowsSortOrder(FieldsType::class, 'time', 'desc')

                    //Sort checkbox field
                    ->click('[data-table-head] .th-checkbox')
                    ->assertRowsSortOrder(FieldsType::class, 'checkbox', 'asc')
                    ->click('[data-table-head] .th-checkbox')
                    ->assertRowsSortOrder(FieldsType::class, 'checkbox', 'desc')

                    //Sort radio field
                    ->click('[data-table-head] .th-radio')
                    ->assertRowsSortOrder(FieldsType::class, 'radio', 'asc')
                    ->click('[data-table-head] .th-radio')
                    ->assertRowsSortOrder(FieldsType::class, 'radio', 'desc');
        });
    }
}
