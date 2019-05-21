<?php

namespace Gogol\Admin\Tests\Browser\Pages;

use Admin;
use Artisan;
use Carbon\Carbon;
use Gogol\Admin\Tests\App\Models\Articles\Article;
use Gogol\Admin\Tests\App\Models\FieldsType;
use Gogol\Admin\Tests\Browser\BrowserTestCase;
use Gogol\Admin\Tests\Browser\DuskBrowser;
use Gogol\Admin\Tests\Traits\DropDatabase;

class TableRowsTest extends BrowserTestCase
{
    use DropDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->withFactories(__DIR__.'/../../Factories');
    }

    private function getColumnsList()
    {
        return [
            'id', 'string', 'text', 'select', 'integer', 'decimal', 'file', 'date',
            'datetime', 'time', 'checkbox', 'radio'
        ];
    }

    /** @test */
    public function test_full_grid_size()
    {
        //Create 100 articles
        factory(FieldsType::class, 10)->create();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(FieldsType::class)
                    ->assertHasClass('li[data-size="full"]', 'active');
        });
    }

    /** @test */
    public function test_small_grid_size()
    {
        //Create 100 articles
        factory(Article::class, 10)->create();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Article::class)
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
    public function test_rows_limit()
    {
        //Create 100 articles
        factory(FieldsType::class, 100)->create();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(FieldsType::class);

            //Check if default rows grid has 10 items
            $this->assertCount(10, $browser->getRows(FieldsType::class));

            //Paginate on 50 items, wait for and check if rows changed
            $browser->changeRowsLimit(50)->pause(300);
            $this->assertCount(50, $browser->getRows(FieldsType::class));

            //Paginate on 100 items, wait for and check if rows changed
            $browser->changeRowsLimit(100)->pause(300);
            $this->assertCount(100, $browser->getRows(FieldsType::class));

            //Check if same limit is set after page reload
            $browser->script('window.location.reload()');
            $this->assertCount(100, $browser->getRows(FieldsType::class));
        });
    }

    private function createArticleMoviesList()
    {
        $movies = [
            date('Y-m-10 H:i:s') => 'titanic',
            date('Y-m-11 H:i:s') => 'avengers',
            date('Y-m-12 H:i:s') => 'shrek',
            date('Y-m-14 H:i:s') => 'captain marvel',
            date('Y-m-15 H:i:s') => 'aquaman',
            date('Y-m-16 H:i:s') => 'star is born',
            date('Y-m-18 H:i:s') => 'hastrman',
            date('Y-m-19 H:i:s') => 'barefoot',
            date('Y-m-20 H:i:s') => 'hellboy',
            date('Y-m-21 H:i:s') => 'sprider-man',
            date('Y-m-22 H:i:s') => 'superman',
            date('Y-m-26 H:i:s') => 'john wick',
        ];

        //Create 10 articles with movie names
        $i = 0;
        foreach ($movies as $date => $movie)
        {
            factory(Article::class)->create([
                'name' => $movie,
                'score' => $i++,
                'content' => 'my-search-content',
                'created_at' => $date,
            ]);
        }
    }

    /** @test */
    public function test_searchbar()
    {
        $this->createArticleMoviesList();

        $this->browse(function (DuskBrowser $browser) {
            $browser->openModelPage(Article::class);

            //Search for word
            $browser->type('[data-search-bar] input[data-search-text]', 'man')->pause(700);
            $this->assertCount(4, $rows = $browser->getRows(Article::class));
            $this->assertEquals(array_values(array_map(function($item){
                return $item['name'];
            }, $rows)), ['superman', 'sprider-man', 'hastrman', 'aquaman']);

            //Search by column
            $browser->click('[data-search-bar] button.dropdown-toggle')
                    ->click('[data-search-bar] [data-field="score"] a')
                    ->valueWithEvent('[data-search-bar] input[data-search-text]', 9)->pause(700);
            $this->assertCount(1, $rows = $browser->getRows(Article::class));
            $this->assertEquals(array_values(array_map(function($item){
                return $item['name'];
            }, $rows)), ['sprider-man']);

            //Search by interval from 9 to 11
            $browser->click('[data-interval] button')
                    ->type('[data-search-bar] input[data-search-interval-text]', 11)->pause(700);
            $this->assertCount(3, $rows = $browser->getRows(Article::class));
            $this->assertEquals(array_values(array_map(function($item){
                return $item['name'];
            }, $rows)), ['john wick', 'superman', 'sprider-man']);

            //Close interval and test searching by date
            $browser->click('[data-interval] button')
                    ->click('[data-search-bar] button.dropdown-toggle')
                    ->click('[data-search-bar] [data-field="created_at"] a')
                    ->click('[data-search-bar] input[data-search-date]')->pause(500)
                    ->clickDatePicker(date('16.m.Y'))
                    ->pause(300);
            $this->assertCount(1, $rows = $browser->getRows(Article::class));
            $this->assertEquals(array_values(array_map(function($item){
                return $item['name'];
            }, $rows)), ['star is born']);

            //Search by interval date 16 to 20
            $browser->click('[data-interval] button')
                    ->click('[data-search-bar] input[data-search-interval-date]')->pause(500)
                    ->clickDatePicker(date('20.m.Y'))
                    ->pause(300);
            $this->assertCount(4, $rows = $browser->getRows(Article::class));
            $this->assertEquals(array_values(array_map(function($item){
                return $item['name'];
            }, $rows)), ['hellboy', 'barefoot', 'hastrman', 'star is born']);
        });
    }
}