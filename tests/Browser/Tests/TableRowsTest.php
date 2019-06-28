<?php

namespace Admin\Tests\Browser\Tests;

use Admin;
use Artisan;
use Carbon\Carbon;
use Admin\Tests\App\Models\Articles\Article;
use Admin\Tests\App\Models\Articles\Tag;
use Admin\Tests\App\Models\Fields\FieldsRelation;
use Admin\Tests\App\Models\Fields\FieldsType;
use Admin\Tests\App\Models\Tree\Model3;
use Admin\Tests\Browser\BrowserTestCase;
use Admin\Tests\Browser\DuskBrowser;
use Admin\Tests\Browser\Concerns\SeedTrait;
use Admin\Tests\Concerns\DropDatabase;

class TableRowsTest extends BrowserTestCase
{
    use DropDatabase,
        SeedTrait;

    private function getColumnsList()
    {
        return [
            'id', 'string', 'text', 'select', 'integer', 'decimal', 'file', 'date',
            'datetime', 'time', 'checkbox', 'radio', 'custom'
        ];
    }

    /** @test */
    public function test_full_grid_size()
    {
        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(FieldsType::class)
                    ->assertHasClass('li[data-size="full"]', 'active');
        });
    }

    /** @test */
    public function test_with_childs_grid_size()
    {
        //Create 100 articles
        factory(Article::class, 10)->create();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Article::class)
                    ->assertHasClass('li[data-size="full"]', 'active');
        });
    }

    /** @test */
    public function test_medium_grid_size()
    {
        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Tag::class)
                    ->assertHasClass('li[data-size="medium"]', 'active');
        });
    }

    /** @test */
    public function test_small_grid_size()
    {
        //Create 100 articles
        Model3::create([ 'field1' => 'test value' ]);

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Model3::class)
                    ->assertHasClass('li[data-size="small"]', 'active');
        });
    }

    /** @test */
    public function test_available_columns()
    {
        //Create 100 articles
        factory(FieldsType::class, 10)->create();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(FieldsType::class)
                    ->assertVisibleColumnsList(FieldsType::class, $this->getColumnsList());
        });
    }

    /** @test */
    public function test_columns_visibility_changed()
    {
        //Create 100 articles
        factory(FieldsType::class, 10)->create();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(FieldsType::class)
                    ->press(trans('admin::admin.rows-list'));

                    $hide_columns = ['string', 'text', 'select', 'checkbox'];

                    //Hide columns
                    foreach ($hide_columns as $column)
                        $browser->click('*[fields-list] input[data-column="'.$column.'"]');

                    //Check if all columns except hidden are available
                    $browser->assertVisibleColumnsList(FieldsType::class, array_diff($this->getColumnsList(), $hide_columns));
        });
    }

    /** @test */
    public function test_hidden_columns_visibility_changed_with_relations_values()
    {
        //Create article testing data for relation purposes
        $this->createArticleMoviesList();

        //Create testing rows with belongsToMany relations
        $row = $this->getFieldsRelationFormData();
        FieldsRelation::create($this->buildDbData(FieldsRelation::class, $row));
        $this->saveFieldRelationsValues(FieldsRelation::class, $row);

        $this->browse(function (DuskBrowser $browser) use ($row) {
            $browser->openModelPage(FieldsRelation::class)
                    ->press(trans('admin::admin.rows-list'));

                    $show_columns = ['relation1_id', 'relation_multiple1', 'relation_multiple2', 'relation_multiple3'];

                    //Hide columns
                    foreach ($show_columns as $column)
                        $browser->click('*[fields-list] input[data-column="'.$column.'"]');

                    $visible = array_merge(['id', 'relation2_id', 'relation3_id'], $show_columns);
                    asort($visible);

                    //Check if all columns except hidden are available
                    //then wait 100ms for generating belongsToMany columnd, and then test...
                    $browser->pause(100)
                            ->assertVisibleColumnsList(FieldsRelation::class, $visible)
                            ->assertTableRowExists(FieldsRelation::class, $this->getHiddenColumnsRowData($row));
        });
    }

    //Parse data items into table row format
    private function getHiddenColumnsRowData($row)
    {
        return array_map(function($item){
            if ( is_array($item) )
                $item = implode(', ', $item);

            return is_string($item) ? $this->strLimit($item, 20) : $item;
        }, ['id' => 1] + $row);
    }

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
            $browser->changeRowsLimit(50)->pause(100);
            $this->assertCount(50, $browser->getRows(FieldsType::class));

            //Paginate on 100 items, wait for and check if rows changed
            $browser->changeRowsLimit(100)->pause(100);
            $this->assertCount(100, $browser->getRows(FieldsType::class));

            //Check if same limit is set after page reload
            $browser->script('window.location.reload()');
            $this->assertCount(100, $browser->getRows(FieldsType::class));
        });
    }

    /** @test */
    public function test_searchbar_text_integers_dates_and_intervals()
    {
        $this->createArticleMoviesList();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Article::class);

            //Search for word
            $browser->type('[data-search-bar] input[data-search-text]', 'man')->pause(400)
                    ->assertColumnRowData(Article::class, 'name', ['superman', 'spider-man', 'hastrman', 'aquaman']);

            //Search by column
            $browser->click('[data-search-bar] button.dropdown-toggle')
                    ->click('[data-search-bar] [data-field="score"] a')
                    ->valueWithEvent('[data-search-bar] input[data-search-text]', 9)->pause(400)
                    ->assertColumnRowData(Article::class, 'name', ['spider-man']);

            //Search by interval from 9 to 11
            $browser->click('[data-interval] button')
                    ->type('[data-search-bar] input[data-search-interval-text]', 11)->pause(400)
                    ->assertColumnRowData(Article::class, 'name', ['john wick', 'superman', 'spider-man']);

            //Close interval and test searching by date
            $browser->click('[data-interval] button')
                    ->click('[data-search-bar] button.dropdown-toggle')
                    ->click('[data-search-bar] [data-field="created_at"] a')
                    ->click('[data-search-bar] input[data-search-date]')->pause(100)
                    ->clickDatePicker(date('16.m.Y'))->pause(100)
                    ->assertColumnRowData(Article::class, 'name', ['star is born']);

            //Search by interval date 16 to 20
            $browser->click('[data-interval] button')
                    ->click('[data-search-bar] input[data-search-interval-date]')->pause(300)
                    ->clickDatePicker(date('20.m.Y'))->pause(300)
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
                    ->click('[data-search-bar] [data-field="type"] a')
                    ->setChosenValue('[data-search-bar] [data-search-select]', 'moovie')
                    ->assertColumnRowData(Tag::class, 'article_id', ['avengers', 'avengers', 'avengers', 'titanic'])

                    //Test belongsTo relation filter
                    ->click('[data-search-bar] button.dropdown-toggle')
                    ->click('[data-search-bar] [data-field="article_id"] a')
                    ->setChosenValue('[data-search-bar] [data-search-select]', 'avengers')
                    ->assertColumnRowData(Tag::class, 'type', ['blog', 'moovie', 'moovie', 'moovie']);
        });
    }

    /** @test */
    public function test_pagination()
    {
        $this->createArticleMoviesList();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Article::class)
                    ->changeRowsLimit(5)->pause(100);

            //Check if pagination has correct number of pages
            $browser->assertHasClass('[data-pagination] li:contains(1)', 'active');
            $paginationItems = $browser->script('return $("[data-pagination] li:not([data-pagination-next]):not([data-pagination-prev])")')[0];
            $this->assertCount(3, $paginationItems);

            //Test next page button
            $browser->click('[data-pagination-next] a')->pause(100)
                    ->assertHasClass('[data-pagination] li:contains(2)', 'active')
                    ->assertColumnRowData(Article::class, 'name', ['hastrman', 'star is born', 'aquaman', 'captain marvel', 'shrek']);

            //Test prev page button
            $browser->click('[data-pagination-prev] a')->pause(100)
                    ->assertHasClass('[data-pagination] li:contains(1)', 'active')
                    ->assertColumnRowData(Article::class, 'name', ['john wick', 'superman', 'spider-man', 'hellboy', 'barefoot']);

            //Test number page button
            $browser->jsClick('[data-pagination] li:contains(3) a')->pause(100)
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
            $this->assertCount(15, $paginationItems);
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

    /*
     * Drag and drop, does not work drag&drop as axcepted
     */
    // public function test_drag_and_drop()
    // {
    //     //Create articles
    //     factory(Article::class, 5)->create();

    //     $this->browse(function (DuskBrowser $browser) {
    //         $browser->openModelPage(Article::class)
    //                 ->pause(300)
    //                 ->drag('tr[data-id="3"]', 'tr[data-id="5"]');

    //         $browser->assertColumnRowData(Article::class, 'id', [3, 5, 4, 2, 1]);
    //     });
    // }
}